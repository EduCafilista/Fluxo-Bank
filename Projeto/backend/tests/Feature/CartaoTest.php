<?php

namespace Tests\Feature;

use App\Models\Cartao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** RF07/RF12 + RN38/RN39: cartão físico, virtual e flag de compras on-line. */
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
}
