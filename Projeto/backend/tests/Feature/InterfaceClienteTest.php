<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Plano;
use App\Models\User;
use App\Services\CartoesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InterfaceClienteTest extends TestCase
{
    use RefreshDatabase;

    public function test_estados_vazios_exibem_texto_e_catalogo_esta_disponivel_apos_migrar(): void
    {
        $this->autenticarWeb($this->usuarioComConta());

        foreach ([
            'cards' => 'Você ainda não tem cartões',
            'subscriptions' => 'Sem planos contratados',
            'travel' => 'Nenhuma viagem informada',
            'support' => 'Nenhum chamado registrado',
            'pix' => 'Nenhum Pix recente',
        ] as $pagina => $texto) {
            $this->get(route('web.'.$pagina))->assertOk()->assertSee($texto)->assertDontSee('@include(', false);
        }

        $this->get(route('web.subscriptions'))
            ->assertSee('Contratar Fluxo Essencial')
            ->assertSee('Nenhuma recorrência identificada');
    }

    public function test_fluxo_web_de_assinatura_exibe_itens_cobranca_e_cancelamento(): void
    {
        $user = $this->usuarioComConta();
        $this->autenticarWeb($user);

        $plano = Plano::where('nome', 'Fluxo Mais')->firstOrFail();
        $item = Item::where('nome', 'Seguro de conta')->firstOrFail();
        $this->post(route('web.subscriptions.store'), ['plano_id' => $plano->id, 'dia_cobranca' => 21])
            ->assertRedirect()->assertSessionHas('sucesso', 'Plano contratado.');

        $assinatura = $user->cliente->assinaturas()->firstOrFail();
        $this->post(route('web.subscriptions.items.store', $assinatura), ['item_id' => $item->id])->assertRedirect();

        $this->get(route('web.subscriptions'))->assertOk()
            ->assertSee('Dia de cobrança 21')
            ->assertSee('Cobranças recentes')
            ->assertSee('Seguro de conta')
            ->assertDontSee('Contratar Fluxo Mais');

        $this->delete(route('web.subscriptions.items.delete', [$assinatura, $item]), ['motivo' => 'Solicitação de teste'])->assertRedirect();
        $this->delete(route('web.subscriptions.delete', $assinatura))->assertRedirect();
        $this->get(route('web.subscriptions'))->assertOk()->assertSee('Cancelada')->assertSee('Contratar Fluxo Mais');
    }

    public function test_simulador_autoriza_apenas_cartao_do_cliente_e_reutiliza_comprovante(): void
    {
        config(['services.fluxo.simulacoes_financeiras' => true]);
        $user = $this->usuarioComConta([], 10000);
        $cartoes = app(CartoesService::class);
        $proprio = $cartoes->emitirFisico($user->conta, 'DEBITO');
        $outro = $cartoes->emitirFisico($this->usuarioComConta()->conta, 'DEBITO');
        $this->autenticarWeb($user);

        $this->get(route('web.cards'))->assertOk()->assertSee('Simular compra')->assertSee('Cartão de débito físico');
        $dados = ['valor_simulacao' => '25,00', 'estabelecimento' => 'Mercado', 'idempotencia' => (string) Str::uuid()];
        $this->post(route('web.cards.simulation'), ['cartao_id' => $outro->id] + $dados)->assertNotFound();

        $dados['cartao_id'] = $proprio->id;
        $primeira = $this->post(route('web.cards.simulation'), $dados)->assertRedirect();
        $this->post(route('web.cards.simulation'), $dados)->assertRedirect($primeira->headers->get('Location'));
        $this->assertSame(7500, $user->conta->saldo());
    }

    private function autenticarWeb(User $user): void
    {
        $this->actingAs($user, 'web')->withSession([
            'fluxo_api_token' => $user->createToken('sessao-web')->plainTextToken,
            'fluxo_ultima_atividade' => now()->timestamp,
        ]);
    }
}
