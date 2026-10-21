<?php

namespace Database\Seeders;

use App\Models\Conta;
use App\Models\Lancamento;
use App\Models\Transacao;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // SOMENTE para testes locais. Não rode em produção.
    // Cria 2 clientes; a Ana começa com R$ 1.000,00.
    public function run(): void
    {
        $ana = User::create(['name' => 'Ana', 'email' => 'ana@fluxo.test', 'password' => 'senha123']);
        $bruno = User::create(['name' => 'Bruno', 'email' => 'bruno@fluxo.test', 'password' => 'senha123']);

        $contaAna = Conta::create(['user_id' => $ana->id, 'numero' => '0001-1']);
        Conta::create(['user_id' => $bruno->id, 'numero' => '0002-2']);

        $deposito = Transacao::create(['descricao' => 'Depósito inicial']);
        Lancamento::create([
            'transacao_id' => $deposito->id,
            'conta_id' => $contaAna->id,
            'tipo' => 'credito',
            'valor_centavos' => 100000,
        ]);
    }
}
