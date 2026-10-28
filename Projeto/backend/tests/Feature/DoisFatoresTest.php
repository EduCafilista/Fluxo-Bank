<?php

namespace Tests\Feature;

use App\Models\CodigoVerificacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DoisFatoresTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioComDoisFatores(): User
    {
        $user = $this->usuarioComConta();
        $user->update(['dois_fatores_ativo' => true, 'password' => Hash::make('senha123')]);

        return $user->refresh();
    }

    public function test_login_com_2fa_devolve_desafio_e_nao_token_definitivo(): void
    {
        $user = $this->usuarioComDoisFatores();

        $resposta = $this->postJson("{$this->api}/login", [
            'email' => $user->email,
            'password' => 'senha123',
        ])
            ->assertOk()
            ->assertJsonPath('precisa_2fa', true)
            ->assertJsonStructure(['token_temporario', 'codigo_teste', 'expira_em']);

        $this->assertDatabaseHas('codigos_verificacao', [
            'user_id' => $user->id,
            'finalidade' => CodigoVerificacao::FINALIDADE_LOGIN,
        ]);
        $this->assertNull($resposta->json('token'));
    }

    public function test_codigo_correto_libera_token(): void
    {
        $user = $this->usuarioComDoisFatores();

        $desafio = $this->postJson("{$this->api}/login", ['email' => $user->email, 'password' => 'senha123']);
        $temporario = $desafio->json('token_temporario');
        $codigo = $desafio->json('codigo_teste');

        $this->withHeaders(['Authorization' => 'Bearer '.$temporario])
            ->postJson("{$this->api}/2fa/validar", ['codigo' => $codigo])
            ->assertOk()
            ->assertJsonStructure(['token', 'expira_em']);
    }

    public function test_token_temporario_nao_acessa_rotas_de_negocio(): void
    {
        $user = $this->usuarioComDoisFatores();

        $temporario = $this->postJson("{$this->api}/login", ['email' => $user->email, 'password' => 'senha123'])
            ->json('token_temporario');

        $this->withHeaders(['Authorization' => 'Bearer '.$temporario])
            ->getJson("{$this->api}/conta")
            ->assertStatus(403)
            ->assertJsonPath('precisa_2fa', true);
    }

    public function test_codigo_errado_incrementa_tentativas(): void
    {
        $user = $this->usuarioComDoisFatores();

        $temporario = $this->postJson("{$this->api}/login", ['email' => $user->email, 'password' => 'senha123'])
            ->json('token_temporario');

        $this->withHeaders(['Authorization' => 'Bearer '.$temporario])
            ->postJson("{$this->api}/2fa/validar", ['codigo' => '000000'])
            ->assertStatus(401);

        $this->assertSame(1, CodigoVerificacao::where('user_id', $user->id)->first()->tentativas);
    }

    public function test_codigo_nao_pode_ser_reutilizado(): void
    {
        $user = $this->usuarioComDoisFatores();

        $desafio = $this->postJson("{$this->api}/login", ['email' => $user->email, 'password' => 'senha123']);
        $codigo = $desafio->json('codigo_teste');
        $temporario = $desafio->json('token_temporario');

        $this->withHeaders(['Authorization' => 'Bearer '.$temporario])
            ->postJson("{$this->api}/2fa/validar", ['codigo' => $codigo])
            ->assertOk();

        $novoTemporario = $this->postJson("{$this->api}/login", ['email' => $user->email, 'password' => 'senha123'])
            ->json('token_temporario');

        $this->withHeaders(['Authorization' => 'Bearer '.$novoTemporario])
            ->postJson("{$this->api}/2fa/validar", ['codigo' => $codigo])
            ->assertStatus(401);
    }

    public function test_cinco_falhas_bloqueiam_login(): void
    {
        $user = $this->usuarioComConta();
        $user->update(['password' => Hash::make('senha123')]);

        foreach (range(1, 5) as $tentativa) {
            $this->postJson("{$this->api}/login", ['email' => $user->email, 'password' => 'errada']);
        }

        $this->postJson("{$this->api}/login", ['email' => $user->email, 'password' => 'senha123'])
            ->assertStatus(423);
    }
}
