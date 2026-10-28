<?php

namespace Tests\Feature;

use App\Models\Conta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContaTest extends TestCase
{
    use RefreshDatabase;

    public function test_saldo_e_extrato(): void
    {
        $user = $this->usuarioComConta([], 50000);

        $this->withHeaders($this->autenticarSem2fa($user))
            ->getJson("{$this->api}/conta")
            ->assertOk()
            ->assertJsonPath('saldo_centavos', 50000)
            ->assertJsonPath('conta.status', 'ATIVA')
            ->assertJsonPath('conta.pode_debitar', true);

        $this->withHeaders($this->autenticarSem2fa($user))
            ->getJson("{$this->api}/conta/extrato")
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonStructure(['extrato' => [['id', 'tipo', 'valor_centavos', 'data']]]);
    }

    public function test_extrato_limita_a_50_por_pagina(): void
    {
        $user = $this->usuarioComConta([], 1000);

        $this->withHeaders($this->autenticarSem2fa($user))
            ->getJson("{$this->api}/conta/extrato?per_page=999")
            ->assertOk()
            ->assertJsonPath('por_pagina', 50);
    }

    public function test_cliente_bloqueia_e_desbloqueia_a_propria_conta(): void
    {
        $user = $this->usuarioComConta([], 1000);
        $h = $this->autenticarSem2fa($user);

        $this->withHeaders($h)->postJson("{$this->api}/conta/bloqueio", ['motivo' => 'Suspeita de acesso'])
            ->assertOk()
            ->assertJsonPath('conta.bloqueada', true);

        $this->assertSame(Conta::STATUS_BLOQUEADA, $user->conta->refresh()->status);

        $this->withHeaders($h)->deleteJson("{$this->api}/conta/bloqueio")
            ->assertOk()
            ->assertJsonPath('conta.bloqueada', false);
    }

    public function test_encerramento_exige_saldo_zero(): void
    {
        $user = $this->usuarioComConta([], 1000);
        $h = $this->autenticarSem2fa($user);

        $this->withHeaders($h)->deleteJson("{$this->api}/conta")
            ->assertStatus(422)
            ->assertJsonPath('erro', 'Só é possível encerrar a conta com saldo zerado.');
    }

    public function test_encerra_conta_zerada(): void
    {
        $user = $this->usuarioComConta();
        $h = $this->autenticarSem2fa($user);

        $this->withHeaders($h)->deleteJson("{$this->api}/conta")->assertOk();
        $this->assertSame(Conta::STATUS_ENCERRADA, $user->conta->refresh()->status);
    }

    public function test_conta_pendente_de_kyc_nao_debita(): void
    {
        $user = $this->usuarioComConta();
        $user->conta->update(['status_abertura' => Conta::ABERTURA_PENDENTE, 'status' => Conta::STATUS_PENDENTE]);
        $outro = $this->usuarioComConta();

        $this->withHeaders($this->autenticarSem2fa($user))
            ->postJson("{$this->api}/transferencias", [
                'conta_destino_id' => $outro->conta->id,
                'valor_centavos' => 1000,
            ])
            ->assertStatus(403)
            ->assertJsonPath('erro', 'Conta com abertura não aprovada: não é possível debitar.');
    }

    public function test_rota_inexistente_em_v1_retorna_404_sem_redirecionar(): void
    {
        $this->getJson("{$this->api}/rota-inexistente")
            ->assertNotFound()
            ->assertHeaderMissing('Location')
            ->assertJsonPath('erro', 'Rota não encontrada.')
            ->assertJsonPath('versao', 'v1');

        $this->postJson("{$this->api}/rota-inexistente")
            ->assertNotFound()
            ->assertHeaderMissing('Location')
            ->assertJsonPath('erro', 'Rota não encontrada.')
            ->assertJsonPath('versao', 'v1');

        $redirect = $this->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->getJson('/api/conta')
            ->assertStatus(308)
            ->assertJsonPath('versao', 'v1');

        $location = (string) $redirect->headers->get('Location');
        $this->assertStringStartsWith('https://', $location);
        $this->assertStringEndsWith('/api/v1/conta', $location);
        $this->assertStringNotContainsString('/api/v1/v1', $location);
    }

    public function test_especificacao_openapi_disponivel_em_docs_e_api_v1(): void
    {
        $this->get('/docs/openapi.yaml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/yaml; charset=UTF-8')
            ->assertSee('openapi: 3.0.3');

        $this->get("{$this->api}/openapi.yaml")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/yaml; charset=UTF-8')
            ->assertSee('openapi: 3.0.3');

        $this->getJson('/')
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }
}
