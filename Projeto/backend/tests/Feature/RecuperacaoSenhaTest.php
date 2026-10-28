<?php

namespace Tests\Feature;

use App\Models\ChavePix;
use App\Models\CodigoVerificacao;
use App\Models\LogAuditoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RecuperacaoSenhaTest extends TestCase
{
    use RefreshDatabase;

    private function adminAutenticado(): User
    {
        return User::factory()->backoffice(User::PERFIL_ADMIN)->create([
            'password' => Hash::make('AdminForte123'),
        ]);
    }

    public function test_geracao_valida_de_codigo_de_6_digitos_pelo_admin(): void
    {
        $admin = $this->adminAutenticado();
        $cliente = $this->usuarioComConta();

        $resposta = $this->withHeaders($this->autenticarSem2fa($admin))
            ->postJson("{$this->api}/backoffice/contas/{$cliente->conta->id}/recuperacao-senha")
            ->assertCreated()
            ->assertJsonPath('conta_id', $cliente->conta->id)
            ->assertJsonPath('numero_conta', $cliente->conta->numero)
            ->assertJsonPath('validade_minutos', 10)
            ->assertJsonPath('max_tentativas', 5);

        $codigo = (string) $resposta->json('codigo');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $codigo);

        $registro = CodigoVerificacao::where('user_id', $cliente->id)
            ->where('finalidade', CodigoVerificacao::finalidadeRecuperacao($cliente->conta->id))
            ->first();

        $this->assertNotNull($registro);
        $this->assertNotSame($codigo, $registro->codigo_hash);
        $this->assertSame(64, strlen($registro->codigo_hash));
    }

    public function test_comando_artisan_gera_codigo_de_6_digitos_por_cpf_email_ou_conta(): void
    {
        $cliente = $this->usuarioComConta();

        $this->artisan("senha:gerar-codigo {$cliente->email}")
            ->assertSuccessful();

        $this->assertDatabaseHas('codigos_verificacao', [
            'user_id' => $cliente->id,
            'finalidade' => CodigoVerificacao::finalidadeRecuperacao($cliente->conta->id),
        ]);

        $this->artisan('senha:gerar-codigo inexistente@fluxo.test')
            ->assertFailed();
    }

    public function test_localizacao_e_normalizacao_por_cpf_email_e_telefone_sem_expor_id_interno(): void
    {
        $admin = $this->adminAutenticado();
        $cliente = $this->usuarioComConta(['email' => 'maria.silva@fluxo.test']);
        $cpf = $cliente->cliente->cpf;
        $cpfFormatado = substr($cpf, 0, 3).'.'.substr($cpf, 3, 3).'.'.substr($cpf, 6, 3).'-'.substr($cpf, 9, 2);

        ChavePix::create([
            'conta_id' => $cliente->conta->id,
            'tipo_chave' => ChavePix::TIPO_TELEFONE,
            'valor_chave' => '11987654321',
            'status' => ChavePix::STATUS_ATIVA,
            'registrado_em' => now(),
        ]);

        // 1) Validação informando CPF com máscara
        $codigo1 = $this->withHeaders($this->autenticarSem2fa($admin))
            ->postJson("{$this->api}/backoffice/recuperacao-senha/emitir", ['identificador' => $cpfFormatado])
            ->assertCreated()
            ->json('codigo');

        $respCpf = $this->postJson("{$this->api}/senha/recuperacao/validar", [
            'cpf' => $cpfFormatado,
            'codigo' => $codigo1,
        ])->assertOk();

        $this->assertArrayNotHasKey('conta_id', $respCpf->json());
        $this->assertNotEmpty($respCpf->json('token_redefinicao'));

        // 2) Validação informando e-mail em maiúsculas
        $codigo2 = $this->withHeaders($this->autenticarSem2fa($admin))
            ->postJson("{$this->api}/backoffice/recuperacao-senha/emitir", ['identificador' => 'MARIA.SILVA@FLUXO.TEST'])
            ->assertCreated()
            ->json('codigo');

        $respEmail = $this->postJson("{$this->api}/senha/recuperacao/validar", [
            'email' => '  MARIA.SILVA@FLUXO.TEST  ',
            'codigo' => $codigo2,
        ])->assertOk();

        $this->assertArrayNotHasKey('conta_id', $respEmail->json());

        // 3) Validação informando telefone formatado (+55 e máscara)
        $codigo3 = $this->withHeaders($this->autenticarSem2fa($admin))
            ->postJson("{$this->api}/backoffice/recuperacao-senha/emitir", ['identificador' => '(11) 98765-4321'])
            ->assertCreated()
            ->json('codigo');

        $respTel = $this->postJson("{$this->api}/senha/recuperacao/validar", [
            'telefone' => '+55 (11) 98765-4321',
            'codigo' => $codigo3,
        ])->assertOk();

        $this->assertArrayNotHasKey('conta_id', $respTel->json());
    }

    public function test_codigo_vinculado_a_conta_correta_nao_recupera_outra_conta(): void
    {
        $admin = $this->adminAutenticado();
        $clienteA = $this->usuarioComConta();
        $clienteB = $this->usuarioComConta();

        $codigoA = $this->withHeaders($this->autenticarSem2fa($admin))
            ->postJson("{$this->api}/backoffice/contas/{$clienteA->conta->id}/recuperacao-senha")
            ->assertCreated()
            ->json('codigo');

        // Tentar validar o código da conta A informando o CPF ou e-mail da conta B falha
        $this->postJson("{$this->api}/senha/recuperacao/validar", [
            'identificador' => $clienteB->email,
            'codigo' => $codigoA,
        ])->assertStatus(401);

        // Validar com o identificador da conta A funciona, mas tentar redefinir passando o identificador de B falha
        $tokenA = $this->postJson("{$this->api}/senha/recuperacao/validar", [
            'identificador' => $clienteA->cliente->cpf,
            'codigo' => $codigoA,
        ])->assertOk()->json('token_redefinicao');

        $this->postJson("{$this->api}/senha/recuperacao/redefinir", [
            'identificador' => $clienteB->email,
            'token_redefinicao' => $tokenA,
            'password' => 'NovaSenhaB123',
            'password_confirmation' => 'NovaSenhaB123',
        ])->assertStatus(401);
    }

    public function test_codigo_invalido_e_recusado_e_incrementa_tentativas(): void
    {
        $admin = $this->adminAutenticado();
        $cliente = $this->usuarioComConta();

        $codigo = $this->withHeaders($this->autenticarSem2fa($admin))
            ->postJson("{$this->api}/backoffice/contas/{$cliente->conta->id}/recuperacao-senha")
            ->json('codigo');

        $codigoErrado = $codigo === '000000' ? '999999' : '000000';

        $this->postJson("{$this->api}/senha/recuperacao/validar", [
            'identificador' => $cliente->email,
            'codigo' => $codigoErrado,
        ])
            ->assertStatus(401)
            ->assertJsonPath('erro', 'Código inválido. Tentativas restantes: 4.');

        $registro = CodigoVerificacao::where('user_id', $cliente->id)
            ->where('finalidade', CodigoVerificacao::finalidadeRecuperacao($cliente->conta->id))
            ->first();

        $this->assertSame(1, $registro->tentativas);
    }

    public function test_codigo_expirado_e_recusado(): void
    {
        $admin = $this->adminAutenticado();
        $cliente = $this->usuarioComConta();

        $codigo = $this->withHeaders($this->autenticarSem2fa($admin))
            ->postJson("{$this->api}/backoffice/contas/{$cliente->conta->id}/recuperacao-senha")
            ->json('codigo');

        CodigoVerificacao::where('user_id', $cliente->id)
            ->where('finalidade', CodigoVerificacao::finalidadeRecuperacao($cliente->conta->id))
            ->update(['expira_em' => now()->subMinute()]);

        $this->postJson("{$this->api}/senha/recuperacao/validar", [
            'identificador' => $cliente->cliente->cpf,
            'codigo' => $codigo,
        ])
            ->assertStatus(401)
            ->assertJsonPath('erro', 'Código expirado. Solicite um novo código.');
    }

    public function test_codigo_ja_utilizado_nao_pode_ser_reutilizado(): void
    {
        $admin = $this->adminAutenticado();
        $cliente = $this->usuarioComConta();

        $codigo = $this->withHeaders($this->autenticarSem2fa($admin))
            ->postJson("{$this->api}/backoffice/contas/{$cliente->conta->id}/recuperacao-senha")
            ->json('codigo');

        $this->postJson("{$this->api}/senha/recuperacao/validar", [
            'identificador' => $cliente->email,
            'codigo' => $codigo,
        ])->assertOk();

        $this->postJson("{$this->api}/senha/recuperacao/validar", [
            'identificador' => $cliente->email,
            'codigo' => $codigo,
        ])
            ->assertStatus(401)
            ->assertJsonPath('erro', 'Código já utilizado. Solicite um novo código.');
    }

    public function test_limite_de_cinco_tentativas_excedido_bloqueia_codigo(): void
    {
        $admin = $this->adminAutenticado();
        $cliente = $this->usuarioComConta();

        $codigo = $this->withHeaders($this->autenticarSem2fa($admin))
            ->postJson("{$this->api}/backoffice/contas/{$cliente->conta->id}/recuperacao-senha")
            ->json('codigo');

        $codigoErrado = $codigo === '111111' ? '222222' : '111111';

        for ($i = 1; $i <= 4; $i++) {
            $this->postJson("{$this->api}/senha/recuperacao/validar", [
                'identificador' => $cliente->cliente->cpf,
                'codigo' => $codigoErrado,
            ])->assertStatus(401);
        }

        // 5ª tentativa inválida atinge o limite e bloqueia com HTTP 429
        $this->postJson("{$this->api}/senha/recuperacao/validar", [
            'identificador' => $cliente->cliente->cpf,
            'codigo' => $codigoErrado,
        ])->assertStatus(429);

        // Mesmo informando o código correto após o bloqueio, permanece recusado com HTTP 429
        $this->postJson("{$this->api}/senha/recuperacao/validar", [
            'identificador' => $cliente->cliente->cpf,
            'codigo' => $codigo,
        ])->assertStatus(429);
    }

    public function test_identificadores_inexistentes_e_protecao_contra_enumeracao(): void
    {
        $cliente = $this->usuarioComConta();

        $respExistente = $this->postJson("{$this->api}/senha/recuperacao/solicitar", [
            'identificador' => $cliente->email,
        ])->assertOk();

        $respEmailInexistente = $this->postJson("{$this->api}/senha/recuperacao/solicitar", [
            'identificador' => 'nao.existe@fluxo.test',
        ])->assertOk();

        $respCpfInexistente = $this->postJson("{$this->api}/senha/recuperacao/solicitar", [
            'cpf' => '000.000.000-00',
        ])->assertOk();

        $respTelInexistente = $this->postJson("{$this->api}/senha/recuperacao/solicitar", [
            'telefone' => '(11) 90000-0000',
        ])->assertOk();

        $this->assertSame($respExistente->json(), $respEmailInexistente->json());
        $this->assertSame($respExistente->json(), $respCpfInexistente->json());
        $this->assertSame($respExistente->json(), $respTelInexistente->json());

        // Validação sem código emitido para conta existente e para conta inexistente retornam a mesma mensagem
        $valExistenteSemCodigo = $this->postJson("{$this->api}/senha/recuperacao/validar", [
            'identificador' => $cliente->email,
            'codigo' => '123456',
        ])->assertStatus(401);

        $valInexistente = $this->postJson("{$this->api}/senha/recuperacao/validar", [
            'identificador' => 'nao.existe@fluxo.test',
            'codigo' => '123456',
        ])->assertStatus(401);

        $this->assertSame($valExistenteSemCodigo->json('erro'), $valInexistente->json('erro'));
    }

    public function test_recuperacao_completa_login_com_nova_senha_recusa_antiga_e_revoga_tokens_e_sessoes(): void
    {
        $admin = $this->adminAutenticado();
        $cliente = $this->usuarioComConta(['password' => Hash::make('SenhaAntiga123')]);
        $tokenAntigo = $this->autenticar($cliente);

        DB::table('sessions')->insert([
            'id' => 'sessao-ativa-123',
            'user_id' => $cliente->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'dGVzdA==',
            'last_activity' => time(),
        ]);

        // 1) Cliente solicita recuperação informando apenas o CPF
        $this->postJson("{$this->api}/senha/recuperacao/solicitar", [
            'identificador' => $cliente->cliente->cpf,
        ])->assertOk();

        // 2) Operador ADMIN localiza a conta e gera o código de 6 dígitos
        $codigo = $this->withHeaders($this->autenticarSem2fa($admin))
            ->postJson("{$this->api}/backoffice/recuperacao-senha/emitir", [
                'identificador' => $cliente->cliente->cpf,
            ])
            ->assertCreated()
            ->json('codigo');

        // 3) Cliente valida o código informando seu CPF e recebe autorização temporária (sem conta_id exposto)
        $validacao = $this->postJson("{$this->api}/senha/recuperacao/validar", [
            'identificador' => $cliente->cliente->cpf,
            'codigo' => $codigo,
        ])->assertOk();

        $this->assertArrayNotHasKey('conta_id', $validacao->json());
        $tokenRedefinicao = $validacao->json('token_redefinicao');
        $this->assertNotEmpty($tokenRedefinicao);

        // O token temporário de redefinição não permite acessar rotas de negócio
        $this->app['auth']->forgetGuards();
        $this->withHeaders(['Authorization' => 'Bearer '.$tokenRedefinicao])
            ->getJson("{$this->api}/conta")
            ->assertStatus(403);

        // 4) Cliente cadastra a nova senha (sem precisar informar conta_id)
        $this->postJson("{$this->api}/senha/recuperacao/redefinir", [
            'token_redefinicao' => $tokenRedefinicao,
            'password' => 'NovaSenhaSegura456',
            'password_confirmation' => 'NovaSenhaSegura456',
        ])->assertOk();

        // Autorização temporária é de uso único e não pode ser reutilizada
        $this->postJson("{$this->api}/senha/recuperacao/redefinir", [
            'token_redefinicao' => $tokenRedefinicao,
            'password' => 'OutraSenha789',
            'password_confirmation' => 'OutraSenha789',
        ])->assertStatus(401);

        // Sessões e tokens de autenticação anteriores foram revogados
        $this->assertDatabaseMissing('sessions', ['user_id' => $cliente->id]);
        $this->app['auth']->forgetGuards();
        $this->withHeaders(['Authorization' => 'Bearer '.$tokenAntigo])
            ->getJson("{$this->api}/eu")
            ->assertStatus(401);

        // Login com a senha antiga é recusado
        $this->postJson("{$this->api}/login", [
            'email' => $cliente->email,
            'password' => 'SenhaAntiga123',
        ])->assertStatus(401);

        // Login com a senha nova funciona
        $this->postJson("{$this->api}/login", [
            'email' => $cliente->email,
            'password' => 'NovaSenhaSegura456',
        ])
            ->assertOk()
            ->assertJsonStructure(['token']);

        // Logs de auditoria não expõem o código, a nova senha nem o token
        $logsTexto = json_encode(
            LogAuditoria::where('registro_id', (string) $cliente->conta->id)
                ->orWhere('usuario_id', $cliente->id)
                ->pluck('payload')
                ->all()
        );
        $this->assertStringNotContainsString($codigo, (string) $logsTexto);
        $this->assertStringNotContainsString('NovaSenhaSegura456', (string) $logsTexto);
        $this->assertStringNotContainsString($tokenRedefinicao, (string) $logsTexto);
    }

    public function test_usuario_nao_autorizado_nao_pode_gerar_codigos(): void
    {
        $cliente = $this->usuarioComConta();
        $atendente = User::factory()->backoffice(User::PERFIL_ATENDENTE)->create();

        // Sem autenticação -> 401
        $this->postJson("{$this->api}/backoffice/contas/{$cliente->conta->id}/recuperacao-senha")
            ->assertStatus(401);

        // Cliente comum -> 403
        $this->withHeaders($this->autenticarSem2fa($cliente))
            ->postJson("{$this->api}/backoffice/contas/{$cliente->conta->id}/recuperacao-senha")
            ->assertStatus(403);

        // Atendente (não ADMIN) -> 403
        $this->withHeaders($this->autenticarSem2fa($atendente))
            ->postJson("{$this->api}/backoffice/contas/{$cliente->conta->id}/recuperacao-senha")
            ->assertStatus(403)
            ->assertJsonPath('erro', 'Ação restrita ao perfil ADMIN.');
    }

    public function test_compatibilidade_com_2fa_existente_para_admin_e_cliente(): void
    {
        $adminCom2fa = User::factory()
            ->backoffice(User::PERFIL_ADMIN)
            ->comDoisFatores()
            ->create(['password' => Hash::make('Admin2fa123')]);

        $clienteCom2fa = $this->usuarioComConta([
            'dois_fatores_ativo' => true,
            'password' => Hash::make('ClienteAntiga123'),
        ]);

        // Admin faz login e recebe token temporário pendente de 2FA
        $desafioAdmin = $this->postJson("{$this->api}/login", [
            'email' => $adminCom2fa->email,
            'password' => 'Admin2fa123',
        ])->assertOk()->assertJsonPath('precisa_2fa', true);

        $tokenTempAdmin = $desafioAdmin->json('token_temporario');
        $codigo2faAdmin = $desafioAdmin->json('codigo_teste');

        // Com 2FA pendente, o ADMIN não consegue gerar código de recuperação
        $this->withHeaders(['Authorization' => 'Bearer '.$tokenTempAdmin])
            ->postJson("{$this->api}/backoffice/contas/{$clienteCom2fa->conta->id}/recuperacao-senha")
            ->assertStatus(403)
            ->assertJsonPath('precisa_2fa', true);

        // Após concluir o 2FA, o ADMIN consegue gerar o código normalmente
        $tokenAdmin = $this->withHeaders(['Authorization' => 'Bearer '.$tokenTempAdmin])
            ->postJson("{$this->api}/2fa/validar", ['codigo' => $codigo2faAdmin])
            ->assertOk()
            ->json('token');

        $this->app['auth']->forgetGuards();

        $codigoRecuperacao = $this->withHeaders(['Authorization' => 'Bearer '.$tokenAdmin])
            ->postJson("{$this->api}/backoffice/contas/{$clienteCom2fa->conta->id}/recuperacao-senha")
            ->assertCreated()
            ->json('codigo');

        // Cliente com 2FA ativo recupera a senha usando seu e-mail
        $tokenRedefinicao = $this->postJson("{$this->api}/senha/recuperacao/validar", [
            'identificador' => $clienteCom2fa->email,
            'codigo' => $codigoRecuperacao,
        ])->assertOk()->json('token_redefinicao');

        $this->postJson("{$this->api}/senha/recuperacao/redefinir", [
            'token_redefinicao' => $tokenRedefinicao,
            'password' => 'ClienteNova2fa123',
            'password_confirmation' => 'ClienteNova2fa123',
        ])->assertOk();

        // O 2FA do cliente permanece ativo no próximo login com a nova senha
        $this->postJson("{$this->api}/login", [
            'email' => $clienteCom2fa->email,
            'password' => 'ClienteNova2fa123',
        ])
            ->assertOk()
            ->assertJsonPath('precisa_2fa', true)
            ->assertJsonStructure(['token_temporario', 'expira_em']);
    }

    public function test_nao_permite_redefinir_somente_com_cpf_email_ou_telefone_e_aplica_requisitos_de_senha(): void
    {
        $admin = $this->adminAutenticado();
        $cliente = $this->usuarioComConta(['password' => Hash::make('SenhaOriginal123')]);

        // Tentativa de redefinir informando apenas CPF e e-mail sem token de autorização
        $this->postJson("{$this->api}/senha/recuperacao/redefinir", [
            'cpf' => $cliente->cliente->cpf,
            'email' => $cliente->email,
            'password' => 'SenhaInvasao123',
            'password_confirmation' => 'SenhaInvasao123',
        ])->assertStatus(401);

        // Emitir e validar código legítimo
        $codigo = $this->withHeaders($this->autenticarSem2fa($admin))
            ->postJson("{$this->api}/backoffice/contas/{$cliente->conta->id}/recuperacao-senha")
            ->json('codigo');

        $tokenRedefinicao = $this->postJson("{$this->api}/senha/recuperacao/validar", [
            'identificador' => $cliente->email,
            'codigo' => $codigo,
        ])->json('token_redefinicao');

        // Senha curta (< 8 caracteres) é recusada com 422
        $this->postJson("{$this->api}/senha/recuperacao/redefinir", [
            'token_redefinicao' => $tokenRedefinicao,
            'password' => 'curta',
        ])->assertStatus(422);

        // Confirmação divergente é recusada com 422
        $this->postJson("{$this->api}/senha/recuperacao/redefinir", [
            'token_redefinicao' => $tokenRedefinicao,
            'password' => 'SenhaValida123',
            'password_confirmation' => 'OutraSenha123',
        ])->assertStatus(422);
    }
}
