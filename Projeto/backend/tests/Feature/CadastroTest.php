<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Conta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CadastroTest extends TestCase
{
    use RefreshDatabase;

    private const CPF_VALIDO = '529.982.247-25';

    private function dadosValidos(array $sobrescrever = []): array
    {
        return array_merge([
            'nome' => 'Carla Souza',
            'cpf' => self::CPF_VALIDO,
            'data_nascimento' => '1990-05-10',
            'email' => 'carla@fluxo.test',
            'password' => 'senha12345',
        ], $sobrescrever);
    }

    public function test_cadastro_com_dados_validos_cria_usuario_cliente_e_conta(): void
    {
        $this->postJson("{$this->api}/cadastro", $this->dadosValidos())
            ->assertCreated()
            ->assertJsonPath('conta', fn ($numero) => is_string($numero) && $numero !== '')
            ->assertJsonPath('cliente.documento_mascarado', '***.***.***-25')
            ->assertJsonPath('status_abertura', 'APROVADA');

        $this->assertDatabaseHas('users', ['email' => 'carla@fluxo.test']);
        $this->assertDatabaseHas('clientes', ['cpf' => '52998224725', 'nome' => 'Carla Souza', 'status' => 'ATIVO']);
        $this->assertDatabaseCount('contas', 1);
        $this->assertDatabaseHas('contas', ['status' => 'ATIVA', 'status_abertura' => 'APROVADA']);
    }

    public function test_senha_e_salva_com_hash(): void
    {
        $this->postJson("{$this->api}/cadastro", $this->dadosValidos())->assertCreated();

        $user = User::where('email', 'carla@fluxo.test')->firstOrFail();
        $this->assertNotEquals('senha12345', $user->password);
        $this->assertTrue(Hash::check('senha12345', $user->password));
    }

    public function test_cpf_formatado_e_salvo_apenas_com_digitos(): void
    {
        $this->postJson("{$this->api}/cadastro", $this->dadosValidos(['cpf' => '529.982.247-25']))->assertCreated();

        $this->assertDatabaseHas('clientes', ['cpf' => '52998224725']);
    }

    public function test_cpf_com_digito_verificador_errado_e_recusado(): void
    {
        $this->postJson("{$this->api}/cadastro", $this->dadosValidos(['cpf' => '529.982.247-26']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('cpf');
    }

    public function test_cpf_com_todos_os_digitos_iguais_e_recusado(): void
    {
        $this->postJson("{$this->api}/cadastro", $this->dadosValidos(['cpf' => '111.111.111-11']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('cpf');
    }

    public function test_cpf_com_tamanho_errado_e_recusado(): void
    {
        $this->postJson("{$this->api}/cadastro", $this->dadosValidos(['cpf' => '1234']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('cpf');
    }

    public function test_cpf_duplicado_e_recusado(): void
    {
        $this->postJson("{$this->api}/cadastro", $this->dadosValidos())->assertCreated();

        $this->postJson("{$this->api}/cadastro", $this->dadosValidos(['email' => 'outra@fluxo.test']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('cpf');
    }

    public function test_email_duplicado_e_recusado(): void
    {
        $this->postJson("{$this->api}/cadastro", $this->dadosValidos())->assertCreated();

        $this->postJson("{$this->api}/cadastro", $this->dadosValidos(['cpf' => '111.444.777-35']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_senha_curta_e_recusada(): void
    {
        $this->postJson("{$this->api}/cadastro", $this->dadosValidos(['password' => '123']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_data_de_nascimento_futura_e_recusada(): void
    {
        $this->postJson("{$this->api}/cadastro", $this->dadosValidos(['data_nascimento' => now()->addDay()->toDateString()]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('data_nascimento');
    }

    public function test_campos_obrigatorios_sao_exigidos(): void
    {
        $this->postJson("{$this->api}/cadastro", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nome', 'cpf', 'data_nascimento', 'email', 'password']);
    }

    public function test_usuario_cadastrado_consegue_fazer_login(): void
    {
        $this->postJson("{$this->api}/cadastro", $this->dadosValidos())->assertCreated();

        $this->postJson("{$this->api}/login", ['email' => 'carla@fluxo.test', 'password' => 'senha12345'])
            ->assertOk()
            ->assertJsonStructure(['token', 'expira_em', 'precisa_2fa'])
            ->assertJsonPath('precisa_2fa', false);
    }

    public function test_cada_cadastro_gera_numero_de_conta_unico(): void
    {
        $this->postJson("{$this->api}/cadastro", $this->dadosValidos())->assertCreated();
        $this->postJson("{$this->api}/cadastro", $this->dadosValidos([
            'cpf' => '111.444.777-35',
            'email' => 'outro@fluxo.test',
        ]))->assertCreated();

        $this->assertCount(2, array_unique(Conta::pluck('numero')->all()));
        $this->assertCount(2, Cliente::all());
    }

    public function test_cadastro_pj_com_cnpj_valido(): void
    {
        $this->postJson("{$this->api}/cadastro", [
            'tipo_cliente' => 'PJ',
            'razao_social' => 'Padaria Pão Quente Ltda',
            'cnpj' => '11.222.333/0001-81',
            'representante_legal' => 'João da Silva',
            'email' => 'pj@fluxo.test',
            'password' => 'senha12345',
        ])
            ->assertCreated()
            ->assertJsonPath('cliente.tipo', 'PJ')
            ->assertJsonPath('tipo_conta', 'PJ');

        $this->assertDatabaseHas('clientes', ['cnpj' => '11222333000181', 'tipo_cliente' => 'PJ']);
        $this->assertDatabaseHas('contas', ['tipo_conta' => 'PJ']);
    }

    public function test_cadastro_pj_com_cnpj_invalido_e_recusado(): void
    {
        $this->postJson("{$this->api}/cadastro", [
            'tipo_cliente' => 'PJ',
            'razao_social' => 'Empresa Errada',
            'cnpj' => '11.222.333/0001-80',
            'representante_legal' => 'Fulano',
            'email' => 'erro@fluxo.test',
            'password' => 'senha12345',
        ])->assertStatus(422)->assertJsonValidationErrors('cnpj');
    }

    public function test_kyc_recusa_menor_de_idade(): void
    {
        $this->postJson("{$this->api}/cadastro", $this->dadosValidos([
            'data_nascimento' => now()->subYears(15)->toDateString(),
            'email' => 'menor@fluxo.test',
            'cpf' => '111.444.777-35',
        ]))
            ->assertCreated()
            ->assertJsonPath('status_abertura', 'RECUSADA');

        $this->assertDatabaseHas('contas', ['status' => 'BLOQUEADA', 'status_abertura' => 'RECUSADA']);
    }
}
