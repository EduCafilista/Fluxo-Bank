<?php

namespace Tests;

use App\Models\ChavePix;
use App\Models\Cliente;
use App\Models\Conta;
use App\Models\User;
use App\Services\PagamentosService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected string $api = '/api/v1';

    protected function usuarioComConta(array $atributos = [], ?int $saldoCentavos = null): User
    {
        $user = User::factory()->create(array_merge([
            'name' => 'Cliente Teste',
            'email' => 'cliente'.fake()->unique()->numberBetween(1, 99999).'@fluxo.test',
            'password' => Hash::make('senha123'),
        ], $atributos));

        Cliente::factory()->create([
            'user_id' => $user->id,
            'nome' => $user->name,
            'cpf' => $this->cpfAleatorio(),
        ]);

        $conta = Conta::factory()->create([
            'user_id' => $user->id,
            'cliente_id' => $user->cliente->id,
        ]);

        if ($saldoCentavos !== null) {
            $this->depositar($conta, $saldoCentavos);
        }

        return $user->refresh();
    }

    protected function autenticar(User $user): string
    {
        $this->app['auth']->forgetGuards();

        return $user->createToken('teste')->plainTextToken;
    }

    protected function autenticarSem2fa(User $user): array
    {
        return ['Authorization' => 'Bearer '.$this->autenticar($user)];
    }

    protected function comChavePix(User $user, ?string $valor = null): User
    {
        ChavePix::create([
            'conta_id' => $user->conta->id,
            'tipo_chave' => 'ALEATORIA',
            'valor_chave' => $valor ?? 'chave-'.$user->id.'-'.Str::random(8),
            'status' => 'ATIVA',
            'registrado_em' => now(),
        ]);

        return $user;
    }

    protected function depositar(Conta $conta, int $centavos): void
    {
        app(PagamentosService::class)->depositar($conta, $centavos, 'Depósito de teste');
    }

    protected function cpfAleatorio(): string
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
}
