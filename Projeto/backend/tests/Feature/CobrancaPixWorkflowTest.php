<?php

namespace Tests\Feature;

use App\Models\CobrancaPix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CobrancaPixWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_cobranca_tem_validade_padrao_de_cinco_minutos_e_aparece_no_historico(): void
    {
        $titular = $this->comChavePix($this->usuarioComConta());
        $headers = $this->autenticarSem2fa($titular);
        $resposta = $this->withHeaders($headers)->postJson("{$this->api}/pix/cobrancas", [
            'valor_centavos' => 2590, 'descricao' => 'Venda teste',
        ])->assertCreated();

        $this->assertSame(5, $resposta->json('expiracao_minutos'));
        $this->assertTrue($resposta->json('valido'));
        $registro = CobrancaPix::findOrFail($resposta->json('cobranca_id'));
        $this->assertSame('ATIVA', $registro->situacao());
        $this->assertSame($resposta->json('payload_emv'), $registro->payload_emv);
        $this->withHeaders($headers)->getJson("{$this->api}/pix/cobrancas")
            ->assertOk()->assertJsonPath('data.0.situacao', 'ATIVA');
    }

    public function test_cancelamento_impede_pagamento_e_bloqueia_cancelamento_de_terceiros(): void
    {
        $titular = $this->comChavePix($this->usuarioComConta());
        $pagador = $this->usuarioComConta([], 50000);
        $codigo = $this->withHeaders($this->autenticarSem2fa($titular))
            ->postJson("{$this->api}/pix/cobrancas", [
                'valor_centavos' => 1500, 'expiracao_minutos' => 10,
            ])->assertCreated()->json();

        $this->withHeaders($this->autenticarSem2fa($pagador))
            ->deleteJson("{$this->api}/pix/cobrancas/{$codigo['cobranca_id']}")->assertNotFound();

        $this->withHeaders($this->autenticarSem2fa($titular))
            ->deleteJson("{$this->api}/pix/cobrancas/{$codigo['cobranca_id']}")->assertOk();

        $this->withHeaders($this->autenticarSem2fa($pagador))
            ->postJson("{$this->api}/pix/pagamentos/revisar", [
                'payload_emv' => $codigo['payload_emv'],
            ])->assertStatus(409);
    }

    public function test_cobranca_expirada_nao_pode_ser_paga(): void
    {
        $titular = $this->comChavePix($this->usuarioComConta());
        $pagador = $this->usuarioComConta([], 100000);
        $cobranca = $this->withHeaders($this->autenticarSem2fa($titular))
            ->postJson("{$this->api}/pix/cobrancas", [
                'valor_centavos' => 1000, 'expiracao_minutos' => 1,
            ])->assertCreated()->json();

        $this->travel(2)->minutes();
        $this->withHeaders($this->autenticarSem2fa($pagador))
            ->postJson("{$this->api}/pix/pagamentos/revisar", [
                'payload_emv' => $cobranca['payload_emv'],
            ])->assertStatus(422);
    }

    public function test_chave_pix_inexistente_exibe_erro_explicito(): void
    {
        $pagador = $this->usuarioComConta([], 10000);
        $this->withHeaders($this->autenticarSem2fa($pagador))
            ->postJson("{$this->api}/transferencias", [
                'chave_pix' => 'nao-existe@fluxo.test', 'valor_centavos' => 100,
            ])->assertNotFound()->assertJsonPath('erro', 'Chave Pix não encontrada ou inativa.');
    }
}
