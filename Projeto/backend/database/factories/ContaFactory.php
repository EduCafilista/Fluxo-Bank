<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Conta;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Conta> */
class ContaFactory extends Factory
{
    protected $model = Conta::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'cliente_id' => Cliente::factory(),
            'numero' => '0001-'.str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'tipo_conta' => 'CORRENTE',
            'agencia' => '0001',
            'status' => Conta::STATUS_ATIVA,
            'status_abertura' => Conta::ABERTURA_APROVADA,
            'status_abertura_atualizado_em' => now(),
            'bloqueada' => false,
        ];
    }

    public function pendente(): static
    {
        return $this->state(fn () => [
            'status' => Conta::STATUS_PENDENTE,
            'status_abertura' => Conta::ABERTURA_PENDENTE,
        ]);
    }

    public function bloqueada(): static
    {
        return $this->state(fn () => [
            'status' => Conta::STATUS_BLOQUEADA,
            'bloqueada' => true,
        ]);
    }

    public function paraUsuario(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }
}
