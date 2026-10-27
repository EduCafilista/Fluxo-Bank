<?php

namespace Tests\Feature;

use App\Models\Conta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** RF09 + RN37: RBAC, aprovação de KYC, bloqueio por fraude e chamados. */
class BackofficeTest extends TestCase
{
    use RefreshDatabase;

    public function test_cliente_nao_acessa_backoffice(): void
    {
        $cliente = $this->usuarioComConta();

        $this->withHeaders($this->autenticarSem2fa($cliente))
            ->getJson("{$this->api}/backoffice/resumo")
            ->assertForbidden();
    }

    public function test_atendente_ve_resumo(): void
    {
        $atendente = User::factory()->backoffice()->create();

        $this->withHeaders($this->autenticarSem2fa($atendente))
            ->getJson("{$this->api}/backoffice/resumo")
            ->assertOk()
            ->assertJsonStructure(['clientes', 'contas_por_status', 'chamados_abertos', 'logs_auditoria']);
    }

    public function test_aprova_kyc_e_ativa_conta(): void
    {
        $atendente = User::factory()->backoffice()->create();
        $cliente = $this->usuarioComConta();
        $cliente->conta->update([
            'status' => Conta::STATUS_PENDENTE,
            'status_abertura' => Conta::ABERTURA_PENDENTE,
        ]);

        $this->withHeaders($this->autenticarSem2fa($atendente))
            ->postJson("{$this->api}/backoffice/contas/{$cliente->conta->id}/kyc/aprovar")
            ->assertOk()
            ->assertJsonPath('conta.status_abertura', 'APROVADA')
            ->assertJsonPath('conta.status', 'ATIVA');
    }

    public function test_bloqueia_conta_por_fraude(): void
    {
        $atendente = User::factory()->backoffice()->create();
        $cliente = $this->usuarioComConta();

        $this->withHeaders($this->autenticarSem2fa($atendente))
            ->postJson("{$this->api}/backoffice/contas/{$cliente->conta->id}/bloquear-fraude", ['motivo' => 'Padrão atípico'])
            ->assertOk()
            ->assertJsonPath('conta.bloqueada', true);
    }

    public function test_cliente_abre_chamado_e_atendente_responde(): void
    {
        $cliente = $this->usuarioComConta();
        $atendente = User::factory()->backoffice()->create();

        $id = $this->withHeaders($this->autenticarSem2fa($cliente))
            ->postJson("{$this->api}/chamados", ['assunto' => 'Dúvida', 'mensagem' => 'Como faço um Pix?'])
            ->assertCreated()
            ->json('chamado.id');

        $this->withHeaders($this->autenticarSem2fa($atendente))
            ->patchJson("{$this->api}/backoffice/chamados/{$id}", ['resposta' => 'É só usar /pix/cobrancas.'])
            ->assertOk()
            ->assertJsonPath('chamado.status', 'RESOLVIDO');
    }
}
