<?php

namespace Tests\Feature;

use App\Models\Transacao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagamentoTest extends TestCase
{
    use RefreshDatabase;

    private function linhaDigitavel(): string
    {
        return '34191790010104351004791020150008291070026000142';
    }

    public function test_valida_linha_digitavel_sem_debitar_conta(): void
    {
        $user = $this->usuarioComConta([], 20000);
        $h = $this->autenticarSem2fa($user);

        $this->withHeaders($h)
            ->postJson("{$this->api}/boletos/validar", ['linha_digitavel' => $this->linhaDigitavel()])
            ->assertOk()
            ->assertJsonPath('valido', true)
            ->assertJsonPath('digitos', 47);

        $this->withHeaders($h)
            ->postJson("{$this->api}/boletos/validar", ['linha_digitavel' => '12345'])
            ->assertStatus(422)
            ->assertJsonPath('valido', false);
    }

    public function test_paga_boleto_e_debita_a_conta(): void
    {
        $user = $this->usuarioComConta([], 20000);

        $this->withHeaders($this->autenticarSem2fa($user))
            ->postJson("{$this->api}/boletos/pagar", [
                'linha_digitavel' => $this->linhaDigitavel(),
                'valor_centavos' => 5000,
            ])
            ->assertCreated()
            ->assertJsonPath('saldo_centavos', 15000);
    }

    public function test_boleto_com_linha_invalida_e_recusado(): void
    {
        $user = $this->usuarioComConta([], 20000);

        $this->withHeaders($this->autenticarSem2fa($user))
            ->postJson("{$this->api}/boletos/pagar", [
                'linha_digitavel' => '123',
                'valor_centavos' => 5000,
            ])
            ->assertStatus(422)
            ->assertJsonPath('erro', 'Linha digitável inválida: são esperados 47 ou 48 dígitos.');
    }

    public function test_boleto_sem_saldo_e_recusado_sem_gravar(): void
    {
        $user = $this->usuarioComConta([], 1000);

        $this->withHeaders($this->autenticarSem2fa($user))
            ->postJson("{$this->api}/boletos/pagar", [
                'linha_digitavel' => $this->linhaDigitavel(),
                'valor_centavos' => 5000,
            ])
            ->assertStatus(422)
            ->assertJsonPath('erro', 'Saldo insuficiente.');

        $this->assertSame(1, Transacao::count());
    }

    public function test_recebimento_pix_credita_e_fecha_em_zero(): void
    {
        $user = $this->usuarioComConta();

        $this->withHeaders($this->autenticarSem2fa($user))
            ->postJson("{$this->api}/pix/recebimentos", ['valor_centavos' => 7500])
            ->assertCreated()
            ->assertJsonPath('saldo_centavos', 7500);

        $transacao = Transacao::latest('id')->first();
        $this->assertSame(0, $transacao->somaDosLancamentos());
    }

    public function test_recebimento_pix_respeita_idempotencia(): void
    {
        $user = $this->usuarioComConta();
        $headers = $this->autenticarSem2fa($user) + ['Idempotency-Key' => 'pix-rec-001'];

        $this->withHeaders($headers)
            ->postJson("{$this->api}/pix/recebimentos", ['valor_centavos' => 5000])
            ->assertCreated();

        $this->withHeaders($headers)
            ->postJson("{$this->api}/pix/recebimentos", ['valor_centavos' => 5000])
            ->assertCreated();

        $this->assertSame(5000, $user->conta->refresh()->saldo());
    }

    public function test_comprovante_da_transacao(): void
    {
        $user = $this->usuarioComConta([], 10000);
        $outro = $this->usuarioComConta();

        $transacaoId = $this->withHeaders($this->autenticarSem2fa($user))
            ->postJson("{$this->api}/transferencias", [
                'conta_destino_id' => $outro->conta->id,
                'valor_centavos' => 2500,
            ])->json('id');

        $this->withHeaders($this->autenticarSem2fa($user))
            ->getJson("{$this->api}/transacoes/{$transacaoId}")
            ->assertOk()
            ->assertJsonPath('valor_centavos', 2500)
            ->assertJsonPath('partidas_dobradas', true)
            ->assertJsonStructure(['autenticacao', 'lancamentos']);
    }

    public function test_comprovante_de_transacao_alheia_devolve_404(): void
    {
        $dono = $this->usuarioComConta([], 10000);
        $intruso = $this->usuarioComConta();
        $destino = $this->usuarioComConta();

        $transacaoId = $this->withHeaders($this->autenticarSem2fa($dono))
            ->postJson("{$this->api}/transferencias", [
                'conta_destino_id' => $destino->conta->id,
                'valor_centavos' => 1000,
            ])->json('id');

        $this->withHeaders($this->autenticarSem2fa($intruso))
            ->getJson("{$this->api}/transacoes/{$transacaoId}")
            ->assertNotFound();
    }
}
