<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferenciaTest extends TestCase
{
    use RefreshDatabase;

    private function login(string $email): string
    {
        $resposta = $this->postJson('/api/login', ['email' => $email, 'password' => 'senha123']);
        $resposta->assertOk();

        return $resposta->json('token');
    }

    public function test_transferencia_movimenta_o_saldo(): void
    {
        $this->seed(DatabaseSeeder::class);
        $token = $this->login('ana@fluxo.test');

        $this->withToken($token)->postJson('/api/transferencias', [
            'conta_destino_id' => 2,
            'valor_centavos' => 25000,
            'descricao' => 'Teste',
        ])->assertCreated();

        $this->withToken($token)->getJson('/api/conta')
            ->assertOk()
            ->assertJsonPath('saldo_centavos', 75000);
    }

    public function test_nao_permite_transferir_mais_que_o_saldo(): void
    {
        $this->seed(DatabaseSeeder::class);
        $token = $this->login('ana@fluxo.test');

        $this->withToken($token)->postJson('/api/transferencias', [
            'conta_destino_id' => 2,
            'valor_centavos' => 200000,
        ])->assertStatus(422)->assertJsonPath('erro', 'Saldo insuficiente.');
    }

    public function test_rotas_protegidas_exigem_token(): void
    {
        $this->getJson('/api/conta')->assertUnauthorized();
    }
}
