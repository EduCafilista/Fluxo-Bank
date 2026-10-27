<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Cliente> */
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tipo_cliente' => Cliente::TIPO_PF,
            'nome' => fake()->name(),
            'cpf' => $this->cpfValido(),
            'data_nascimento' => fake()->dateTimeBetween('-60 years', '-20 years')->format('Y-m-d'),
            'status' => Cliente::STATUS_ATIVO,
        ];
    }

    public function pessoaJuridica(): static
    {
        return $this->state(fn () => [
            'tipo_cliente' => Cliente::TIPO_PJ,
            'cpf' => null,
            'data_nascimento' => null,
            'razao_social' => fake()->company(),
            'cnpj' => $this->cnpjValido(),
            'representante_legal' => fake()->name(),
        ]);
    }

    /** Gera um CPF matematicamente válido (para não brigar com a Rule Cpf). */
    private function cpfValido(): string
    {
        $base = (string) random_int(100000000, 999999999);
        $digitos = [];

        for ($posicao = 9; $posicao < 11; $posicao++) {
            $soma = 0;
            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $base[$i] * (($posicao + 1) - $i);
            }
            $digitos[] = ((10 * $soma) % 11) % 10;
            $base .= $digitos[count($digitos) - 1];
        }

        return substr($base, 0, 9).implode('', $digitos);
    }

    private function cnpjValido(): string
    {
        $base = (string) random_int(10000000, 99999999).'0001';
        $pesos1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $soma = 0;
        foreach ($pesos1 as $i => $peso) {
            $soma += (int) $base[$i] * $peso;
        }
        $d1 = ($soma % 11) < 2 ? 0 : 11 - ($soma % 11);
        $base .= $d1;
        $pesos2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $soma = 0;
        foreach ($pesos2 as $i => $peso) {
            $soma += (int) $base[$i] * $peso;
        }
        $d2 = ($soma % 11) < 2 ? 0 : 11 - ($soma % 11);

        return $base.$d2;
    }
}
