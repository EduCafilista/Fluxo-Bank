<?php

namespace Tests\Unit;

use App\Support\Sanitizador;
use Tests\TestCase;

class SanitizadorTest extends TestCase
{
    public function test_mascara_email_padrao(): void
    {
        $this->assertSame('j***a@exemplo.com.br', Sanitizador::mascararEmail('joao.silva@exemplo.com.br'));
        $this->assertSame('c***o@banco.com', Sanitizador::mascararEmail('contato@banco.com'));
        $this->assertSame('a***a@fluxo.test', Sanitizador::mascararEmail('ana@fluxo.test'));
    }

    public function test_mascara_email_com_usuario_curto(): void
    {
        $this->assertSame('a***b@fluxo.test', Sanitizador::mascararEmail('ab@fluxo.test'));
        $this->assertSame('*@fluxo.test', Sanitizador::mascararEmail('a@fluxo.test'));
    }

    public function test_mascara_email_com_valores_vazios_ou_invalidos(): void
    {
        $this->assertSame('', Sanitizador::mascararEmail(''));
        $this->assertSame('', Sanitizador::mascararEmail('   '));
        $this->assertSame('***', Sanitizador::mascararEmail('texto-sem-arroba'));
    }

    public function test_mascara_telefone_com_ddd_e_nove_digitos(): void
    {
        $this->assertSame('(11) 9****-4321', Sanitizador::mascararTelefone('11987654321'));
        $this->assertSame('(11) 9****-4321', Sanitizador::mascararTelefone('(11) 98765-4321'));
        $this->assertSame('(11) 9****-4321', Sanitizador::mascararTelefone('+5511987654321'));
    }

    public function test_mascara_telefone_fixo_com_oito_digitos(): void
    {
        $this->assertSame('(11) ****-4444', Sanitizador::mascararTelefone('1133334444'));
        $this->assertSame('(21) ****-3333', Sanitizador::mascararTelefone('(21) 2222-3333'));
    }

    public function test_mascara_telefone_com_valores_vazios_ou_tamanho_invalido(): void
    {
        $this->assertSame('', Sanitizador::mascararTelefone(''));
        $this->assertSame('***', Sanitizador::mascararTelefone('123'));
        $this->assertSame('', Sanitizador::mascararTelefone('sem-digitos'));
    }

    public function test_limpar_aplica_mascara_em_chaves_de_email_e_telefone(): void
    {
        $entrada = [
            'email' => 'cliente@fluxo.test',
            'telefone' => '11987654321',
            'celular' => '(21) 99999-8888',
            'telefone_contato' => '1133334444',
        ];

        $resultado = Sanitizador::limpar($entrada);

        $this->assertSame('c***e@fluxo.test', $resultado['email']);
        $this->assertSame('(11) 9****-4321', $resultado['telefone']);
        $this->assertSame('(21) 9****-8888', $resultado['celular']);
        $this->assertSame('(11) ****-4444', $resultado['telefone_contato']);
    }

    public function test_limpar_preserva_regras_existentes_de_cpf_cnpj_cartao_e_senhas(): void
    {
        $entrada = [
            'cpf' => '123.456.789-00',
            'cnpj' => '12.345.678/0001-99',
            'numero_cartao' => '4111111111111234',
            'senha' => 'segredo123',
            'password' => 'minhasenha',
            'cvv' => '123',
            'nome' => 'Carlos Pereira',
            'valor_centavos' => 5000,
        ];

        $resultado = Sanitizador::limpar($entrada);

        $this->assertSame('***00', $resultado['cpf']);
        $this->assertSame('***99', $resultado['cnpj']);
        $this->assertSame('***34', $resultado['numero_cartao']);
        $this->assertSame('[REMOVIDO]', $resultado['senha']);
        $this->assertSame('[REMOVIDO]', $resultado['password']);
        $this->assertSame('[REMOVIDO]', $resultado['cvv']);
        $this->assertSame('Carlos Pereira', $resultado['nome']);
        $this->assertSame(5000, $resultado['valor_centavos']);
    }

    public function test_limpar_mascara_dados_em_estruturas_aninhadas(): void
    {
        $entrada = [
            'cliente' => [
                'nome' => 'Maria Silva',
                'email' => 'maria@fluxo.test',
                'contato' => [
                    'celular' => '31977778888',
                ],
            ],
            'meta' => [
                'ip' => '192.168.1.1',
            ],
        ];

        $resultado = Sanitizador::limpar($entrada);

        $this->assertSame('Maria Silva', $resultado['cliente']['nome']);
        $this->assertSame('m***a@fluxo.test', $resultado['cliente']['email']);
        $this->assertSame('(31) 9****-8888', $resultado['cliente']['contato']['celular']);
        $this->assertSame('192.168.1.1', $resultado['meta']['ip']);
    }

    public function test_limpar_lida_com_valores_nulos_e_tipos_nao_escalares(): void
    {
        $entrada = [
            'email' => null,
            'telefone' => null,
            'celular' => '',
        ];

        $resultado = Sanitizador::limpar($entrada);

        $this->assertNull($resultado['email']);
        $this->assertNull($resultado['telefone']);
        $this->assertSame('', $resultado['celular']);
    }
}
