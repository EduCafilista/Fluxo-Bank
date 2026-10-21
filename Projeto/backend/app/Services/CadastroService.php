<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Conta;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CadastroService
{
    // Cria usuário, cliente e conta numa só transação: ou cria tudo, ou nada
    public function cadastrarPessoaFisica(array $dados): Conta
    {
        return DB::transaction(function () use ($dados) {
            $user = User::create([
                'name' => $dados['nome'],
                'email' => $dados['email'],
                'password' => $dados['password'], // o cast 'hashed' do User gera o hash
            ]);

            Cliente::create([
                'user_id' => $user->id,
                'nome' => $dados['nome'],
                'cpf' => $dados['cpf'],
                'data_nascimento' => $dados['data_nascimento'],
            ]);

            return Conta::create([
                'user_id' => $user->id,
                'numero' => $this->gerarNumeroConta(),
            ]);
        });
    }

    // Número no formato 0001-000000, garantido único
    private function gerarNumeroConta(): string
    {
        do {
            $numero = '0001-'.str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (Conta::where('numero', $numero)->exists());

        return $numero;
    }
}
