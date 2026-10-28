<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Plano;
use Database\Seeders\CatalogoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssinaturaTest extends TestCase
{
    use RefreshDatabase;

    private function plano(): Plano
    {
        $this->seed(CatalogoSeeder::class);

        return Plano::where('nome', 'Fluxo Mais')->firstOrFail();
    }

    public function test_contrata_plano_com_fatura_proporcional(): void
    {
        $user = $this->usuarioComConta();
        $plano = $this->plano();

        $this->withHeaders($this->autenticarSem2fa($user))
            ->postJson("{$this->api}/assinaturas", ['plano_id' => $plano->id])
            ->assertCreated()
            ->assertJsonStructure(['assinatura' => ['id', 'valor_atual_centavos', 'cobrancas']]);

        $this->assertDatabaseHas('assinaturas', ['cliente_id' => $user->cliente->id, 'status' => 'ATIVA']);
        $this->assertDatabaseCount('cobrancas', 1);
    }

    public function test_retirada_de_item_e_logica_e_recalcula_valor(): void
    {
        $user = $this->usuarioComConta();
        $plano = $this->plano();
        $h = $this->autenticarSem2fa($user);

        $assinaturaId = $this->withHeaders($h)
            ->postJson("{$this->api}/assinaturas", ['plano_id' => $plano->id])
            ->json('assinatura.id');

        $item = Item::where('nome', 'Cartão adicional')->firstOrFail();

        $this->withHeaders($h)
            ->postJson("{$this->api}/assinaturas/{$assinaturaId}/itens", ['item_id' => $item->id])
            ->assertCreated()
            ->assertJsonPath('valor_atual_centavos', $plano->mensalidade_centavos + $item->valor_unitario_centavos);

        $this->withHeaders($h)
            ->deleteJson("{$this->api}/assinaturas/{$assinaturaId}/itens/{$item->id}", ['motivo' => 'Cliente pediu'])
            ->assertOk()
            ->assertJsonPath('valor_atual_centavos', $plano->mensalidade_centavos);

        $this->assertDatabaseHas('itens_assinatura', [
            'assinatura_id' => $assinaturaId,
            'item_id' => $item->id,
            'motivo_exclusao' => 'Cliente pediu',
        ]);
    }
}
