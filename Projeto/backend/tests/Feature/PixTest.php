<?php

namespace Tests\Feature;

use App\Models\ChavePix;
use App\Models\LimitePix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setTime(14, 0, 0));
    }

    public function test_cadastra_chave_pix_e_recusa_duplicada(): void
    {
        $user = $this->usuarioComConta();
        $h = $this->autenticarSem2fa($user);

        $this->withHeaders($h)->postJson("{$this->api}/chaves-pix", [
            'tipo_chave' => 'EMAIL',
            'valor_chave' => 'pix@fluxo.test',
        ])->assertCreated()->assertJsonPath('chave.tipo', 'EMAIL');

        $this->assertDatabaseHas('chaves_pix', ['valor_chave' => 'pix@fluxo.test', 'status' => 'ATIVA']);

        $this->withHeaders($h)->postJson("{$this->api}/chaves-pix", [
            'tipo_chave' => 'EMAIL',
            'valor_chave' => 'pix@fluxo.test',
        ])->assertStatus(409);
    }

    public function test_chave_cpf_e_normalizada_para_digitos(): void
    {
        $user = $this->usuarioComConta();

        $this->withHeaders($this->autenticarSem2fa($user))->postJson("{$this->api}/chaves-pix", [
            'tipo_chave' => 'CPF',
            'valor_chave' => '529.982.247-25',
        ])->assertCreated();

        $this->assertDatabaseHas('chaves_pix', ['valor_chave' => '52998224725']);
    }

    public function test_transferencia_por_chave_pix(): void
    {
        $ana = $this->usuarioComConta([], 100000);
        $bruno = $this->usuarioComConta([]);

        ChavePix::create([
            'conta_id' => $bruno->conta->id,
            'tipo_chave' => 'EMAIL',
            'valor_chave' => 'bruno@pix.test',
            'status' => 'ATIVA',
            'registrado_em' => now(),
        ]);

        $this->withHeaders($this->autenticarSem2fa($ana))->postJson("{$this->api}/transferencias", [
            'chave_pix' => 'bruno@pix.test',
            'valor_centavos' => 15000,
        ])->assertCreated();

        $this->assertSame(85000, $ana->conta->refresh()->saldo());
        $this->assertSame(15000, $bruno->conta->refresh()->saldo());
    }

    public function test_respeita_limite_por_transacao(): void
    {
        $ana = $this->usuarioComConta([], 500000);
        $bruno = $this->usuarioComConta([]);

        LimitePix::create([
            'conta_id' => $ana->conta->id,
            'limite_por_transacao' => 10000,
            'limite_diario' => 1000000,
            'quantidade_diaria' => 20,
            'limite_noturno_por_transacao' => 10000,
            'limite_noturno_diario' => 10000,
            'quantidade_noturna' => 5,
            'limite_sem_chave_por_transacao' => 10000,
            'limite_sem_chave_diario' => 10000,
            'hora_inicio_noturno' => '20:00',
            'hora_fim_noturno' => '06:00',
            'ativo' => true,
        ]);

        $this->withHeaders($this->autenticarSem2fa($ana))->postJson("{$this->api}/transferencias", [
            'conta_destino_id' => $bruno->conta->id,
            'valor_centavos' => 50000,
        ])->assertStatus(422)->assertJsonPath('erro', 'Valor acima do limite por transação (R$ 100,00).');
    }

    public function test_sem_chave_ativa_aplica_teto_reduzido(): void
    {
        $ana = $this->usuarioComConta([], 500000);
        $bruno = $this->usuarioComConta([]);

        $this->withHeaders($this->autenticarSem2fa($ana))->postJson("{$this->api}/transferencias", [
            'conta_destino_id' => $bruno->conta->id,
            'valor_centavos' => 60000,
        ])->assertStatus(422)->assertJsonPath('erro', 'Valor acima do limite por transação (R$ 500,00).');

        $this->withHeaders($this->autenticarSem2fa($ana))->postJson("{$this->api}/transferencias", [
            'conta_destino_id' => $bruno->conta->id,
            'valor_centavos' => 40000,
        ])->assertCreated();
    }

    public function test_endpoint_de_limites_mostra_disponivel(): void
    {
        $user = $this->usuarioComConta([], 100000);

        $this->withHeaders($this->autenticarSem2fa($user))->getJson("{$this->api}/pix/limites")
            ->assertOk()
            ->assertJsonStructure(['janela', 'tem_chave_pix_ativa', 'limite_diario_centavos', 'disponivel_centavos']);
    }

    public function test_cobranca_pix_gera_payload_emv_com_crc_valido(): void
    {
        $user = $this->usuarioComConta();
        ChavePix::create([
            'conta_id' => $user->conta->id,
            'tipo_chave' => 'ALEATORIA',
            'valor_chave' => 'chave-aleatoria-123',
            'status' => 'ATIVA',
            'registrado_em' => now(),
        ]);

        $resposta = $this->withHeaders($this->autenticarSem2fa($user))->postJson("{$this->api}/pix/cobrancas", [
            'valor_centavos' => 2500,
        ])->assertCreated();

        $this->assertTrue($resposta->json('valido'));
        $this->assertStringStartsWith('000201', $resposta->json('payload_emv'));
        $this->assertSame(substr($resposta->json('payload_emv'), -4), $resposta->json('crc16'));
    }
}
