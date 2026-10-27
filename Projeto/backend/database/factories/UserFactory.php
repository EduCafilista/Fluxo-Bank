<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'perfil' => User::PERFIL_CLIENTE,
            'dois_fatores_ativo' => false,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function backoffice(string $perfil = User::PERFIL_ATENDENTE): static
    {
        return $this->state(fn () => ['perfil' => $perfil]);
    }

    public function comDoisFatores(): static
    {
        return $this->state(fn () => ['dois_fatores_ativo' => true]);
    }
}
