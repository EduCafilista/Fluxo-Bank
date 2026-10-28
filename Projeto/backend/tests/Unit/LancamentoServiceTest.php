<?php

namespace Tests\Unit;

use App\Exceptions\PartidaDobradaInvalidaException;
use App\Models\Lancamento;
use App\Services\LancamentoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LancamentoServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_recusa_lancamento_sem_contrapartida(): void
    {
        $user = $this->usuarioComConta();
        $servico = app(LancamentoService::class);

        $this->expectException(PartidaDobradaInvalidaException::class);

        $servico->lancar('DEPOSITO', [
            ['conta_id' => $user->conta->id, 'tipo' => Lancamento::TIPO_CREDITO, 'valor_centavos' => 1000],
        ]);
    }

    public function test_recusa_valores_desbalanceados(): void
    {
        $a = $this->usuarioComConta();
        $b = $this->usuarioComConta();

        $this->expectException(PartidaDobradaInvalidaException::class);

        app(LancamentoService::class)->lancar('TED_INTERNA', [
            ['conta_id' => $a->conta->id, 'tipo' => Lancamento::TIPO_DEBITO, 'valor_centavos' => 1000],
            ['conta_id' => $b->conta->id, 'tipo' => Lancamento::TIPO_CREDITO, 'valor_centavos' => 900],
        ]);
    }

    public function test_credito_e_debito_gravam_duas_linhas(): void
    {
        $a = $this->usuarioComConta();
        $b = $this->usuarioComConta();

        $transacao = app(LancamentoService::class)->transferir($a->conta, $b->conta, 5000, 'teste');

        $this->assertCount(2, $transacao->lancamentos()->get());
        $this->assertSame(0, $transacao->somaDosLancamentos());
    }
}
