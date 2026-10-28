<?php

namespace App\Support;

use App\Models\Cliente;
use App\Models\Conta;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Conta institucional de contrapartida para lançamentos internos (RN02/RN03).
 */
class ContaInterna
{
    public static function obter(): Conta
    {
        $numero = config('fluxo.conta_interna.numero');

        $conta = Conta::where('numero', $numero)->first();

        if ($conta) {
            return $conta;
        }

        return DB::transaction(function () use ($numero) {
            $email = config('fluxo.conta_interna.email');

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => 'Fluxo Tesouraria',
                    'password' => Str::random(40),
                    'email_verified_at' => now(),
                ],
            );

            Cliente::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'tipo_cliente' => 'PJ',
                    'nome' => 'Fluxo Tesouraria',
                    'razao_social' => 'Fluxo Banco Digital Ltda',
                    'cnpj' => '45723174000110',
                    'representante_legal' => 'Diretoria Fluxo',
                    'data_nascimento' => null,
                    'status' => 'ATIVO',
                ],
            );

            return Conta::create([
                'user_id' => $user->id,
                'cliente_id' => $user->cliente?->id,
                'numero' => $numero,
                'tipo_conta' => 'INSTITUCIONAL',
                'agencia' => '0000',
                'status' => 'ATIVA',
                'status_abertura' => 'APROVADA',
                'status_abertura_atualizado_em' => now(),
                'bloqueada' => false,
            ]);
        });
    }
}
