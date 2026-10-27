<?php

namespace Tests\Unit;

use App\Support\Emv;
use Tests\TestCase;

/** RN36: payload EMV do Pix com CRC16 correto. */
class EmvTest extends TestCase
{
    public function test_payload_tem_crc16_valido(): void
    {
        $payload = Emv::pixPayload('52998224725', 1000, 'TXID123', 'FLUXO BANCO DIGITAL', 'SAO PAULO');

        $this->assertSame(substr($payload, -4), Emv::crc16(substr($payload, 0, -4)));
    }

    public function test_payload_contem_campos_obrigatorios(): void
    {
        $payload = Emv::pixPayload('ana@fluxo.test', 2500, 'TXIDABC');

        $this->assertStringStartsWith('000201', $payload);
        $this->assertStringContainsString('BR.GOV.BCB.PIX', $payload);
        $this->assertStringContainsString('5303986', $payload);   // moeda BRL
        $this->assertStringContainsString('5802BR', $payload);    // país
        $this->assertStringContainsString('540525.00', $payload); // tag 54, tamanho 05, valor 25.00
        $this->assertStringContainsString('TXIDABC', $payload);
    }

    public function test_acentos_sao_removidos_do_nome(): void
    {
        $payload = Emv::pixPayload('chave', 100, 'TX', 'JOÃO AÇÃO', 'SÃO PAULO');

        $this->assertStringContainsString('JOAO ACAO', $payload);
        $this->assertStringNotContainsString('Ã', $payload);
    }
}
