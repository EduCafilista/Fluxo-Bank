<?php

namespace Tests\Feature;

use App\Models\AssinaturaCartao;
use App\Models\Cartao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_emite_cartao_fisico_debito_sem_compras_online(): void
    {
        $user = $this->usuarioComConta();

        $this->withHeaders($this->autenticarSem2fa($user))
            ->postJson("{$this->api}/cartoes", ['modalidade' => 'DEBITO'])
            ->assertCreated()
            ->assertJsonPath('cartao.modalidade', 'DEBITO')
            ->assertJsonPath('cartao.fisico.permite_compras_online', false);
    }

    public function test_gera_virtual_a_partir_do_fisico_ativo(): void
    {
        $user = $this->usuarioComConta();
        $h = $this->autenticarSem2fa($user);

        $cartaoId = $this->withHeaders($h)->postJson("{$this->api}/cartoes", ['modalidade' => 'CREDITO'])
            ->json('cartao.id');

        $this->withHeaders($h)->postJson("{$this->api}/cartoes/{$cartaoId}/virtual")
            ->assertCreated()
            ->assertJsonPath('cartao.virtual.gerado_em', fn ($v) => $v !== null);

        $this->assertDatabaseHas('cartoes_virtuais', ['cartao_fisico_id' => Cartao::find($cartaoId)->fisico->id]);
    }

    public function test_nao_permite_gerar_segundo_cartao_virtual_para_mesmo_fisico(): void
    {
        $user = $this->usuarioComConta();
        $h = $this->autenticarSem2fa($user);

        $cartaoId = $this->withHeaders($h)->postJson("{$this->api}/cartoes", ['modalidade' => 'DEBITO'])
            ->json('cartao.id');

        $this->withHeaders($h)->postJson("{$this->api}/cartoes/{$cartaoId}/virtual")
            ->assertCreated();

        $this->withHeaders($h)->postJson("{$this->api}/cartoes/{$cartaoId}/virtual")
            ->assertStatus(409)
            ->assertJsonPath('erro', 'Este cartão já possui um cartão virtual.');
    }

    public function test_virtual_nao_pode_ser_gerado_de_cartao_bloqueado(): void
    {
        $user = $this->usuarioComConta();
        $h = $this->autenticarSem2fa($user);

        $cartaoId = $this->withHeaders($h)->postJson("{$this->api}/cartoes", ['modalidade' => 'DEBITO'])->json('cartao.id');

        $this->withHeaders($h)->patchJson("{$this->api}/cartoes/{$cartaoId}/bloqueio", ['bloquear' => true]);

        $this->withHeaders($h)->postJson("{$this->api}/cartoes/{$cartaoId}/virtual")
            ->assertStatus(422)
            ->assertJsonPath('erro', 'O cartão físico precisa estar ativo para gerar o virtual.');
    }

    public function test_limite_acima_do_teto_e_recusado(): void
    {
        $user = $this->usuarioComConta();
        $h = $this->autenticarSem2fa($user);

        $cartaoId = $this->withHeaders($h)->postJson("{$this->api}/cartoes", ['modalidade' => 'CREDITO'])->json('cartao.id');

        $this->withHeaders($h)->patchJson("{$this->api}/cartoes/{$cartaoId}/limite", ['limite_centavos' => 9999999])
            ->assertStatus(422);

        $this->withHeaders($h)->patchJson("{$this->api}/cartoes/{$cartaoId}/limite", ['limite_centavos' => 150000])
            ->assertOk()
            ->assertJsonPath('cartao.limite_atual_centavos', 150000);
    }

    public function test_flag_de_compras_online_so_existe_no_fisico(): void
    {
        $user = $this->usuarioComConta();
        $h = $this->autenticarSem2fa($user);

        $cartaoId = $this->withHeaders($h)->postJson("{$this->api}/cartoes", ['modalidade' => 'DEBITO'])->json('cartao.id');

        $this->withHeaders($h)->patchJson("{$this->api}/cartoes/{$cartaoId}/compras-online", ['permitir' => true])
            ->assertOk()
            ->assertJsonPath('permite_compras_online', true);

        $this->assertTrue(Cartao::find($cartaoId)->fisico->permite_compras_online);
    }

    public function test_registra_e_cancela_aviso_de_viagem_no_cartao(): void
    {
        $user = $this->usuarioComConta();
        $h = $this->autenticarSem2fa($user);

        $cartaoId = $this->withHeaders($h)->postJson("{$this->api}/cartoes", ['modalidade' => 'CREDITO'])->json('cartao.id');

        $avisoId = $this->withHeaders($h)->postJson("{$this->api}/avisos-viagem", [
            'cartao_id' => $cartaoId,
            'localidades' => ['Lisboa', 'Porto'],
            'data_inicio' => now()->toDateString(),
            'data_fim' => now()->addDays(7)->toDateString(),
        ])
            ->assertCreated()
            ->assertJsonPath('aviso.status', 'ATIVO')
            ->json('aviso.id');

        $this->withHeaders($h)->getJson("{$this->api}/avisos-viagem")
            ->assertOk()
            ->assertJsonCount(1, 'avisos');

        $this->withHeaders($h)->deleteJson("{$this->api}/avisos-viagem/{$avisoId}")
            ->assertOk()
            ->assertJsonPath('aviso.status', 'CANCELADO');
    }

    public function test_lista_e_cancela_assinatura_recorrente_do_cartao(): void
    {
        $user = $this->usuarioComConta();
        $h = $this->autenticarSem2fa($user);

        $cartaoId = $this->withHeaders($h)->postJson("{$this->api}/cartoes", ['modalidade' => 'CREDITO'])->json('cartao.id');

        $recorrencia = AssinaturaCartao::create([
            'cartao_id' => $cartaoId,
            'conta_id' => $user->conta->id,
            'identificador_estabelecimento' => 'NETFLIX-BR',
            'nome_estabelecimento' => 'Netflix',
            'periodicidade' => 'MENSAL',
            'valor_estimado_centavos' => 3990,
            'status' => 'ATIVA',
            'origem' => 'DETECTADA',
            'confianca' => 0.95,
        ]);

        $this->withHeaders($h)->getJson("{$this->api}/assinaturas-cartao?cartao_id={$cartaoId}")
            ->assertOk()
            ->assertJsonCount(1, 'assinaturas_cartao');

        $this->withHeaders($h)->deleteJson("{$this->api}/assinaturas-cartao/{$recorrencia->id}")
            ->assertOk();

        $this->assertDatabaseHas('assinaturas_cartao', [
            'id' => $recorrencia->id,
            'status' => 'CANCELADA',
        ]);
    }
}
