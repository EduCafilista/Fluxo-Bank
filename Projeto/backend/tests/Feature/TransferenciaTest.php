<?php

namespace Tests\Feature;

use App\Models\Conta;
use App\Models\Transacao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RF05/RNF15/RNF16/RNF17.
 * Correção do bug original: nada de "conta_destino_id => 2" fixo — no
 * PostgreSQL o RefreshDatabase (transação) não reinicia as sequences, então
 * o id da conta de destino é obtido em tempo de execução.
 */
class TransferenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_transferencia_movimenta_o_saldo_e_fecha_em_zero(): void
    {
        $ana = $this->usuarioComConta(['email' => 'ana@fluxo.test'], 100000);
        $bruno = $this->usuarioComConta(['email' => 'bruno@fluxo.test']);

        $origem = $ana->conta;
        $destino = $bruno->conta;

        $resposta = $this->withHeaders($this->autenticarSem2fa($ana))
            ->postJson("{$this->api}/transferencias", [
                'conta_destino_id' => $destino->id,
                'valor_centavos' => 25000,
                'descricao' => 'Teste',
            ])
            ->assertCreated()
            ->assertJsonPath('valor_centavos', 25000)
            ->assertJsonPath('partidas_dobradas.fecham', true);

        $this->assertSame(75000, $origem->refresh()->saldo());
        $this->assertSame(25000, $destino->refresh()->saldo());

        // RNF16: a soma dos lançamentos da transação é zero
        $transacao = Transacao::findOrFail($resposta->json('id'));
        $this->assertSame(0, $transacao->somaDosLancamentos());
        $this->assertCount(2, $transacao->lancamentos);
    }

    public function test_nao_permite_transferir_mais_que_o_saldo(): void
    {
        // com chave Pix ativa o teto do dia é o cheio (R$ 5.000,00),
        // então o erro que aparece é o de saldo, não o de limite
        $ana = $this->comChavePix($this->usuarioComConta(['email' => 'ana2@fluxo.test'], 100000));
        $bruno = $this->usuarioComConta(['email' => 'bruno2@fluxo.test']);

        $this->withHeaders($this->autenticarSem2fa($ana))
            ->postJson("{$this->api}/transferencias", [
                'conta_destino_id' => $bruno->conta->id,
                'valor_centavos' => 200000,
            ])
            ->assertStatus(422)
            ->assertJsonPath('erro', 'Saldo insuficiente.');

        // Nada foi gravado além do depósito do setup
        $this->assertSame(100000, $ana->conta->refresh()->saldo());
        $this->assertSame(1, Transacao::count());
    }

    public function test_nao_permite_transferir_para_a_mesma_conta(): void
    {
        $ana = $this->usuarioComConta([], 50000);

        $this->withHeaders($this->autenticarSem2fa($ana))
            ->postJson("{$this->api}/transferencias", [
                'conta_destino_id' => $ana->conta->id,
                'valor_centavos' => 1000,
            ])
            ->assertStatus(422)
            ->assertJsonPath('erro', 'Não é possível transferir para a mesma conta.');
    }

    public function test_rotas_protegidas_exigem_token(): void
    {
        $this->getJson("{$this->api}/conta")->assertUnauthorized();
    }

    public function test_idempotency_key_nao_debita_duas_vezes(): void
    {
        $ana = $this->comChavePix($this->usuarioComConta([], 100000));
        $bruno = $this->usuarioComConta([]);

        $cabecalhos = $this->autenticarSem2fa($ana) + ['Idempotency-Key' => 'pedido-123'];
        $corpo = ['conta_destino_id' => $bruno->conta->id, 'valor_centavos' => 30000];

        $primeira = $this->withHeaders($cabecalhos)->postJson("{$this->api}/transferencias", $corpo)->assertCreated();
        $segunda = $this->withHeaders($cabecalhos)->postJson("{$this->api}/transferencias", $corpo);

        $this->assertContains($segunda->status(), [200, 201]);
        $this->assertSame($primeira->json('id'), $segunda->json('id'));
        $this->assertSame(70000, $ana->conta->refresh()->saldo());
    }

    public function test_conta_bloqueada_nao_transfere(): void
    {
        $ana = $this->usuarioComConta([], 100000);
        $bruno = $this->usuarioComConta([]);

        $ana->conta->update(['bloqueada' => true, 'status' => Conta::STATUS_BLOQUEADA]);

        $this->withHeaders($this->autenticarSem2fa($ana))
            ->postJson("{$this->api}/transferencias", [
                'conta_destino_id' => $bruno->conta->id,
                'valor_centavos' => 1000,
            ])
            ->assertStatus(403)
            ->assertJsonPath('erro', 'Conta bloqueada: não é possível debitar.');
    }
}
