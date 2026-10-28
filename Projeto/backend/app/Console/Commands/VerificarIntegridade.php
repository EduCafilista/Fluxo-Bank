<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VerificarIntegridade extends Command
{
    protected $signature = 'fluxo:verificar';

    protected $description = 'Verifica conexão com o PostgreSQL, migrations, triggers de auditoria e partidas dobradas';

    public function handle(): int
    {
        $falhas = 0;

        $this->info('== Conexão ==');
        try {
            DB::select('select 1');
            $driver = DB::connection()->getDriverName();
            $versao = strtok(DB::selectOne('select version() as v')->v, ',');

            $this->line("  driver ......... {$driver}");
            $this->line('  banco .......... '.DB::getDatabaseName());
            $this->line("  versão ......... {$versao}");

            if ($driver !== 'pgsql') {
                $this->error('  ✗ o driver não é pgsql (o projeto exige somente PostgreSQL)');
                $falhas++;
            } else {
                $this->info('  ✓ PostgreSQL conectado');
            }
        } catch (\Throwable $e) {
            $this->error('  ✗ falha ao conectar: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('== Migrations ==');
        $rodadas = DB::table('migrations')->count();
        $pendentes = $this->migrationsPendentes();

        $this->line("  executadas ..... {$rodadas}");
        $this->line("  pendentes ...... {$pendentes}");

        if ($pendentes > 0) {
            $this->error('  ✗ rode: php artisan migrate');
            $falhas++;
        } else {
            $this->info('  ✓ schema atualizado');
        }

        $this->info('== Auditoria (RF-SEG02/03) ==');
        $triggers = DB::select('select tgname from pg_trigger where not tgisinternal order by tgname');
        $this->line('  triggers ....... '.count($triggers));

        if (count($triggers) === 0) {
            $this->error('  ✗ nenhum trigger: as migrations de auditoria não rodaram');
            $falhas++;
        } else {
            $this->info('  ✓ triggers de auditoria ativos');
        }

        $antes = (int) DB::table('logs_auditoria')->count();

        // Teste real de append-only: o DELETE deve ser ignorado pelo banco
        DB::table('logs_auditoria')->insert([
            'tabela' => 'diagnostico', 'registro_id' => '0', 'operacao' => 'INSERT',
            'categoria' => 'SISTEMA', 'registrado_em' => now(),
        ]);
        DB::table('logs_auditoria')->where('tabela', 'diagnostico')->delete();
        $depois = (int) DB::table('logs_auditoria')->count();

        if ($depois > $antes) {
            $this->info('  ✓ trilha append-only (DELETE bloqueado pelo banco)');
        } else {
            $this->error('  ✗ trilha permitiu DELETE/INSERT indevido');
            $falhas++;
        }

        $this->info('== Partidas dobradas (RN02/RN03) ==');
        $quebradas = DB::select(<<<'SQL'
            SELECT t.id, COALESCE(SUM(CASE WHEN l.tipo = 'credito' THEN l.valor_centavos ELSE -l.valor_centavos END), 0) AS soma
              FROM transacoes t LEFT JOIN lancamentos l ON l.transacao_id = t.id
             GROUP BY t.id
            HAVING COALESCE(SUM(CASE WHEN l.tipo = 'credito' THEN l.valor_centavos ELSE -l.valor_centavos END), 0) <> 0
             ORDER BY t.id
        SQL);

        if (empty($quebradas)) {
            $this->info('  ✓ todas as transações fecham em zero');
        } else {
            $this->error('  ✗ transações com soma != 0: '.collect($quebradas)->pluck('id')->implode(', '));
            $falhas++;
        }

        $this->info('== Tabelas do domínio ==');
        $this->line('  '.implode(', ', collect(DB::select(
            "select tablename from pg_tables where schemaname = 'public' order by tablename"
        ))->pluck('tablename')->all()));

        $this->newLine();

        if ($falhas === 0) {
            $this->info('Tudo certo: backend conectado ao PostgreSQL, schema atualizado e trilha íntegra.');

            return self::SUCCESS;
        }

        $this->error("Encontrei {$falhas} problema(s). Veja os itens marcados com ✗ acima.");

        return self::FAILURE;
    }

    private function migrationsPendentes(): int
    {
        if (! Schema::hasTable('migrations')) {
            return count(glob(database_path('migrations/*.php')));
        }

        $rodadas = DB::table('migrations')->pluck('migration')->all();
        $arquivos = collect(glob(database_path('migrations/*.php')))
            ->map(fn ($arquivo) => basename($arquivo, '.php'))
            ->all();

        return count(array_diff($arquivos, $rodadas));
    }
}
