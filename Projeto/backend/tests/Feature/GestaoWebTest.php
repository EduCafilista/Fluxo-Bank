<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GestaoWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_autocadastro_aparece_no_backoffice_e_admin_pode_editar_e_excluir(): void
    {
        config(['fluxo.kyc.automatico' => false]);
        $admin = User::factory()->create(['perfil' => 'ADMIN']);
        $email = 'novo@fluxo.test';
        $this->post(route('web.register.store'), [
            'tipo_cliente' => 'PF',
            'nome' => 'Novo Titular',
            'email' => $email,
            'cpf' => $this->cpfAleatorio(),
            'data_nascimento' => '1990-05-05',
            'password' => 'teste1234',
            'password_confirmation' => 'teste1234',
            'perfil' => 'ADMIN',
        ])->assertRedirect(route('web.login'));
        $cliente = User::whereEmail($email)->firstOrFail();
        $this->assertSame('CLIENTE', $cliente->perfil);
        $this->assertSame('EM_ANALISE', $cliente->conta->status_abertura);

        $this->sessaoWeb($admin);
        $this->get(route('web.admin.users.index', ['busca' => $email]))->assertOk()->assertSee('Novo Titular');
        $this->get(route('web.admin.accounts.show', $cliente->conta))->assertOk()->assertSee('Aprovar abertura');
        $this->post(route('web.admin.accounts.approve', $cliente->conta))->assertRedirect();
        $this->assertSame('ATIVA', $cliente->conta->refresh()->status);

        $cliente->createToken('teste');
        $this->get(route('web.admin.users.edit', $cliente))->assertOk()->assertSee('Excluir usuário');
        $this->patch(route('web.admin.users.update', $cliente), [
            'name' => 'Titular Alterado',
            'email' => 'alterado@fluxo.test',
            'data_nascimento' => '1990-05-05',
            'password' => 'novasenha',
            'password_confirmation' => 'novasenha',
        ])->assertRedirect();
        $this->assertSame('Titular Alterado', $cliente->refresh()->cliente->nome);
        $this->assertTrue(Hash::check('novasenha', $cliente->password));
        $this->assertSame(0, $cliente->tokens()->count());

        $this->depositar($cliente->conta, 100);
        $this->delete(route('web.admin.users.destroy', $cliente), ['confirmacao' => '1'])
            ->assertSessionHas('erro');
        $this->assertDatabaseHas('users', ['id' => $cliente->id, 'deleted_at' => null]);

        $this->delete(route('web.admin.users.destroy', $admin), ['confirmacao' => '1'])
            ->assertSessionHas('erro');

        $semSaldo = $this->usuarioComConta();
        $conta = $semSaldo->conta;
        $this->delete(route('web.admin.users.destroy', $semSaldo), ['confirmacao' => '1'])
            ->assertRedirect(route('web.admin.users.index'));
        $this->assertSoftDeleted($semSaldo);
        $this->assertSame('ENCERRADA', $conta->refresh()->status);
        $this->assertNull($conta->cliente->cpf);
        $this->assertDatabaseHas('logs_auditoria', ['operacao' => 'USUARIO_EXCLUIDO', 'usuario_id' => $admin->id]);
    }

    public function test_apenas_admin_cria_usuario_e_bloqueio_de_fraude_exige_admin_para_reverter(): void
    {
        $admin = User::factory()->create(['perfil' => 'ADMIN']);
        $atendente = User::factory()->create(['perfil' => 'ATENDENTE']);
        $cliente = $this->usuarioComConta();
        $dados = [
            'nome' => 'Nova Atendente',
            'email' => 'atendente-novo@fluxo.test',
            'perfil' => 'ATENDENTE',
            'password' => 'senhateste',
            'password_confirmation' => 'senhateste',
        ];

        $this->sessaoWeb($atendente);
        $this->post(route('web.admin.users.store'), $dados)->assertForbidden();
        $this->post(route('web.admin.accounts.block', $cliente->conta), ['motivo' => 'Análise manual'])->assertRedirect();
        $this->post(route('web.admin.accounts.unblock', $cliente->conta))->assertForbidden();

        $this->sessaoWeb($cliente);
        $this->get(route('web.admin.users.index'))->assertForbidden();
        $this->delete(route('web.profile.unblock'))->assertSessionHas('erro');
        $this->assertTrue($cliente->conta->refresh()->bloqueio_backoffice);

        $this->sessaoWeb($admin);
        $this->post(route('web.admin.users.store'), $dados)->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => $dados['email'], 'perfil' => 'ATENDENTE']);
        $this->post(route('web.admin.accounts.unblock', $cliente->conta))->assertRedirect();
        $this->assertTrue($cliente->conta->refresh()->podeDebitar());
    }

    private function sessaoWeb(User $user): void
    {
        $this->actingAs($user, 'web')->withSession([
            'fluxo_api_token' => $user->createToken('web-teste')->plainTextToken,
            'fluxo_ultima_atividade' => now()->timestamp,
        ]);
    }
}
