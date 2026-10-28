<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Conta;
use App\Models\User;
use App\Services\CadastroService;
use App\Services\CartoesService;
use App\Services\ChavePixService;
use App\Services\PagamentosService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder de dados de demonstração para ambientes local e de teste.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CatalogoSeeder::class);

        $cadastro = app(CadastroService::class);
        $pagamentos = app(PagamentosService::class);

        $ana = $this->clientePf($cadastro, [
            'nome' => 'Ana Souza',
            'cpf' => '52998224725',
            'data_nascimento' => '1990-01-01',
            'email' => 'ana@fluxo.test',
            'password' => 'senha123',
        ]);

        $bruno = $this->clientePf($cadastro, [
            'nome' => 'Bruno Lima',
            'cpf' => '11144477735',
            'data_nascimento' => '1992-06-15',
            'email' => 'bruno@fluxo.test',
            'password' => 'senha123',
        ]);

        if ($ana->saldo() === 0) {
            $pagamentos->depositar($ana, 100000, 'Depósito inicial de demonstração');
        }

        $chaves = app(ChavePixService::class);
        foreach ([[$ana, 'EMAIL', 'ana@fluxo.test'], [$ana, 'ALEATORIA', 'ana-pix-aleatoria'], [$bruno, 'EMAIL', 'bruno@fluxo.test']] as [$conta, $tipo, $valor]) {
            if (! $conta->chavesPix()->where('valor_chave', $valor)->exists()) {
                $chaves->cadastrar($conta, $tipo, $valor);
            }
        }

        $cartoes = app(CartoesService::class);
        $fisicoDebito = $ana->cartoes()->where('modalidade', 'DEBITO')->whereHas('fisico')->first();

        if (! $fisicoDebito) {
            $fisicoDebito = $cartoes->emitirFisico($ana, 'DEBITO', 'Rua das Flores, 100 - Uberlândia/MG');
        }

        if ($fisicoDebito->fisico && ! $fisicoDebito->fisico->cartaoVirtual()->exists()) {
            $cartoes->gerarVirtual($fisicoDebito);
        }

        if (! $ana->cartoes()->where('modalidade', 'CREDITO')->whereHas('fisico')->exists()) {
            $cartoes->emitirFisico($ana, 'CREDITO', 'Rua das Flores, 100 - Uberlândia/MG');
        }

        User::updateOrCreate(
            ['email' => 'admin@fluxo.test'],
            [
                'name' => 'Equipe Fluxo',
                'password' => Hash::make('senha123'),
                'perfil' => User::PERFIL_ADMIN,
                'email_verified_at' => now(),
            ],
        );

        $this->command?->info('Demo pronta: contas '.$ana->numero.' (R$ 1.000,00) e '.$bruno->numero.' | login: ana@fluxo.test / senha123');
    }

    private function clientePf(CadastroService $cadastro, array $dados): Conta
    {
        $existente = Conta::whereHas('user', fn ($q) => $q->where('email', $dados['email']))->first();

        if ($existente) {
            return $existente;
        }

        $clienteExiste = Cliente::where('cpf', $dados['cpf'])->exists();

        if ($clienteExiste) {
            $user = User::where('email', $dados['email'])->firstOrFail();
            $cliente = Cliente::where('cpf', $dados['cpf'])->firstOrFail();

            return app(CadastroService::class)->abrirConta($user, $cliente);
        }

        return $cadastro->cadastrarPessoaFisica($dados);
    }
}
