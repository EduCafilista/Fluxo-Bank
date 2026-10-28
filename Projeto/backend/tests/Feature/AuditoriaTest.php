<?php

namespace Tests\Feature;

use App\Models\LogAuditoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    public function test_requisicao_http_gera_registro_com_categoria(): void
    {
        $this->getJson("{$this->api}/health")->assertOk();
        $this->assertSame(0, LogAuditoria::count());

        $user = $this->usuarioComConta();
        $this->withHeaders($this->autenticarSem2fa($user))->getJson("{$this->api}/conta")->assertOk();

        $log = LogAuditoria::where('categoria', LogAuditoria::CATEGORIA_CONSULTA)->latest('id')->first();

        $this->assertNotNull($log, 'A consulta deveria ter sido registrada');
        $this->assertSame($user->id, $log->usuario_id);
        $this->assertSame('api/v1/conta', $log->rota);
        $this->assertSame('GET', $log->metodo);
        $this->assertSame(200, $log->status_http);
        $this->assertNotNull($log->request_id);
    }

    public function test_login_gera_registro_de_acesso(): void
    {
        $user = $this->usuarioComConta();
        $user->update(['password' => bcrypt('senha123')]);

        $this->postJson("{$this->api}/login", ['email' => $user->email, 'password' => 'senha123'])->assertOk();

        $this->assertTrue(
            LogAuditoria::where('categoria', LogAuditoria::CATEGORIA_ACESSO)->exists(),
            'O login deveria gerar registro de ACESSO',
        );
    }

    public function test_senha_nunca_e_gravada_no_payload(): void
    {
        $this->postJson("{$this->api}/login", ['email' => 'x@fluxo.test', 'password' => 'super-secreta']);

        $conteudo = LogAuditoria::pluck('payload')->map(fn ($p) => json_encode($p))->implode(' ');

        $this->assertStringNotContainsString('super-secreta', $conteudo);
        $this->assertStringContainsString('[REMOVIDO]', $conteudo);
    }

    public function test_trigger_registra_old_new_e_diff(): void
    {
        $user = $this->usuarioComConta();

        $user->conta->update(['bloqueada' => true]);

        $log = LogAuditoria::where('tabela', 'contas')
            ->where('operacao', LogAuditoria::OPERACAO_UPDATE)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame((string) $user->conta->id, $log->registro_id);
        $this->assertArrayHasKey('bloqueada', $log->diff);
        $this->assertFalse($log->antes['bloqueada']);
        $this->assertTrue($log->depois['bloqueada']);
        $this->assertNotNull($log->hash_registro);
    }

    public function test_trilha_e_append_only(): void
    {
        $user = $this->usuarioComConta();
        $user->conta->update(['bloqueada' => true]);

        $total = LogAuditoria::count();
        $this->assertGreaterThan(0, $total);

        DB::table('logs_auditoria')->delete();
        $this->assertSame($total, LogAuditoria::count());

        DB::table('logs_auditoria')->update(['categoria' => 'HACKEADA']);
        $this->assertSame(0, LogAuditoria::where('categoria', 'HACKEADA')->count());
    }

    public function test_backoffice_consulta_trilha_e_cliente_nao(): void
    {
        $admin = User::factory()->backoffice()->create();
        $cliente = $this->usuarioComConta();

        $this->withHeaders($this->autenticarSem2fa($admin))
            ->getJson("{$this->api}/backoffice/auditoria")
            ->assertOk()
            ->assertJsonStructure(['registros', 'total', 'append_only']);

        $this->withHeaders($this->autenticarSem2fa($cliente))
            ->getJson("{$this->api}/backoffice/auditoria")
            ->assertForbidden();
    }
}
