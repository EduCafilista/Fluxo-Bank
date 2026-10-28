<?php

namespace Tests\Feature;

use App\Models\Agendamento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendamentoTest extends TestCase
{
    use RefreshDatabase;

    public function test_agenda_transferencia_futura(): void
    {
        $ana = $this->usuarioComConta([], 100000);
        $bruno = $this->usuarioComConta([]);

        $this->withHeaders($this->autenticarSem2fa($ana))
            ->postJson("{$this->api}/agendamentos", [
                'conta_destino_id' => $bruno->conta->id,
                'valor_centavos' => 20000,
                'agendado_para' => now()->addDays(2)->toIso8601String(),
            ])
            ->assertCreated()
            ->assertJsonPath('agendamento.status', 'AGENDADO');
    }

    public function test_data_no_passado_e_recusada(): void
    {
        $ana = $this->usuarioComConta([], 100000);
        $bruno = $this->usuarioComConta([]);

        $this->withHeaders($this->autenticarSem2fa($ana))
            ->postJson("{$this->api}/agendamentos", [
                'conta_destino_id' => $bruno->conta->id,
                'valor_centavos' => 20000,
                'agendado_para' => now()->subDay()->toIso8601String(),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('agendado_para');
    }

    public function test_worker_executa_agendamento_vencido(): void
    {
        $ana = $this->usuarioComConta([], 100000);
        $bruno = $this->usuarioComConta([]);
        $h = $this->autenticarSem2fa($ana);

        $id = $this->withHeaders($h)->postJson("{$this->api}/agendamentos", [
            'conta_destino_id' => $bruno->conta->id,
            'valor_centavos' => 30000,
            'agendado_para' => now()->addHour()->toIso8601String(),
        ])->json('agendamento.id');

        Agendamento::findOrFail($id)->update(['agendado_para' => now()->subMinute()]);

        $this->artisan('agendamentos:processar')->assertSuccessful();

        $this->assertSame(Agendamento::STATUS_EXECUTADO, Agendamento::findOrFail($id)->status);
        $this->assertSame(70000, $ana->conta->refresh()->saldo());
        $this->assertSame(30000, $bruno->conta->refresh()->saldo());
    }

    public function test_worker_nao_executa_duas_vezes_o_mesmo_agendamento(): void
    {
        $ana = $this->usuarioComConta([], 100000);
        $bruno = $this->usuarioComConta([]);

        $id = $this->withHeaders($this->autenticarSem2fa($ana))->postJson("{$this->api}/agendamentos", [
            'conta_destino_id' => $bruno->conta->id,
            'valor_centavos' => 10000,
            'agendado_para' => now()->addHour()->toIso8601String(),
        ])->json('agendamento.id');

        Agendamento::findOrFail($id)->update(['agendado_para' => now()->subMinute()]);

        $this->artisan('agendamentos:processar')->assertSuccessful();
        $this->artisan('agendamentos:processar')->assertSuccessful();

        $this->assertSame(90000, $ana->conta->refresh()->saldo());
    }

    public function test_cancelamento_impede_execucao(): void
    {
        $ana = $this->usuarioComConta([], 100000);
        $bruno = $this->usuarioComConta([]);
        $h = $this->autenticarSem2fa($ana);

        $id = $this->withHeaders($h)->postJson("{$this->api}/agendamentos", [
            'conta_destino_id' => $bruno->conta->id,
            'valor_centavos' => 10000,
            'agendado_para' => now()->addHour()->toIso8601String(),
        ])->json('agendamento.id');

        $this->withHeaders($h)->deleteJson("{$this->api}/agendamentos/{$id}")->assertOk();

        Agendamento::findOrFail($id)->update(['agendado_para' => now()->subMinute()]);
        $this->artisan('agendamentos:processar')->assertSuccessful();

        $this->assertSame(Agendamento::STATUS_CANCELADO, Agendamento::findOrFail($id)->status);
        $this->assertSame(100000, $ana->conta->refresh()->saldo());
    }
}
