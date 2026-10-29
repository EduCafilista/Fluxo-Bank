<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    
    private array $tabelas = [
        'users',
        'clientes',
        'contas',
        'transacoes',
        'lancamentos',
        'chaves_pix',
        'limites_pix',
        'consumos_limites_pix',
        'cartoes',
        'cartoes_fisicos',
        'cartoes_virtuais',
        'assinaturas_cartao',
        'agendamentos',
        'avisos_viagem',
        'planos',
        'assinaturas',
        'itens',
        'itens_assinatura',
        'cobrancas',
        'chamados_suporte',
    ];

    public function up(): void
    {
        foreach ($this->tabelas as $tabela) {
            DB::unprepared("
                DROP TRIGGER IF EXISTS tg_auditoria_{$tabela} ON {$tabela};
                CREATE TRIGGER tg_auditoria_{$tabela}
                    AFTER INSERT OR UPDATE OR DELETE ON {$tabela}
                    FOR EACH ROW EXECUTE FUNCTION fn_auditoria();
            ");
        }

        DB::unprepared('REVOKE UPDATE, DELETE ON logs_auditoria FROM PUBLIC;');
    }

    public function down(): void
    {
        foreach ($this->tabelas as $tabela) {
            DB::unprepared("DROP TRIGGER IF EXISTS tg_auditoria_{$tabela} ON {$tabela};");
        }
    }
};
