<?php

namespace Database\Seeders;

use App\Models\Lancamento;
use App\Models\Transacao;
use App\Services\CadastroService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // SOMENTE para testes locais. Não rode em produção.
    // Cria 2 clientes pelo mesmo serviço do cadastro; a Ana começa com R$ 1.000,00.
    public function run(): void
    {
        $cadastro = app(CadastroService::class);

        $contaAna = $cadastro->cadastrarPessoaFisica([
            'nome' => 'Ana',
            'cpf' => '52998224725',
            'data_nascimento' => '1990-01-01',
            'email' => 'ana@fluxo.test',
            'password' => 'senha123',
        ]);

        $cadastro->cadastrarPessoaFisica([
            'nome' => 'Bruno',
            'cpf' => '11144477735',
            'data_nascimento' => '1992-06-15',
            'email' => 'bruno@fluxo.test',
            'password' => 'senha123',
        ]);

        $deposito = Transacao::create(['descricao' => 'Depósito inicial']);
        Lancamento::create([
            'transacao_id' => $deposito->id,
            'conta_id' => $contaAna->id,
            'tipo' => 'credito',
            'valor_centavos' => 100000,
        ]);
    }
}
