<?php

namespace App\Services;

use App\Exceptions\RegraDeNegocioException;
use App\Models\Cliente;
use App\Models\Conta;
use App\Models\LogAuditoria;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CadastroService
{
    public function cadastrarPessoaFisica(array $dados): Conta
    {
        return $this->cadastrar($dados, Cliente::TIPO_PF);
    }

    public function cadastrarPessoaJuridica(array $dados): Conta
    {
        return $this->cadastrar($dados, Cliente::TIPO_PJ);
    }

    private function cadastrar(array $dados, string $tipoCliente): Conta
    {
        return DB::transaction(function () use ($dados, $tipoCliente) {
            $user = User::create([
                'name' => $dados['nome'],
                'email' => $dados['email'],
                'password' => $dados['password'],
                'perfil' => User::PERFIL_CLIENTE,
                'dois_fatores_ativo' => (bool) ($dados['dois_fatores_ativo'] ?? config('fluxo.dois_fatores.ativo_por_padrao')),
            ]);

            $cliente = Cliente::create([
                'user_id' => $user->id,
                'tipo_cliente' => $tipoCliente,
                'nome' => $dados['nome'],
                'cpf' => $tipoCliente === Cliente::TIPO_PF ? ($dados['cpf'] ?? null) : null,
                'razao_social' => $dados['razao_social'] ?? null,
                'cnpj' => $tipoCliente === Cliente::TIPO_PJ ? ($dados['cnpj'] ?? null) : null,
                'data_nascimento' => $dados['data_nascimento'] ?? null,
                'representante_legal' => $dados['representante_legal'] ?? null,
                'status' => Cliente::STATUS_PENDENTE,
            ]);

            $conta = $this->abrirConta($user, $cliente, $dados['tipo_conta'] ?? ($tipoCliente === Cliente::TIPO_PJ ? 'PJ' : 'CORRENTE'));

            $this->executarKyc($conta);

            app(AuditoriaService::class)->registrar(
                LogAuditoria::CATEGORIA_ACESSO,
                'CADASTRO',
                ['cliente_id' => $cliente->id, 'tipo_cliente' => $tipoCliente, 'conta' => $conta->numero],
                'clientes',
                (string) $cliente->id,
                $user->id,
            );

            return $conta->refresh();
        });
    }

    public function abrirConta(User $user, Cliente $cliente, string $tipoConta = 'CORRENTE'): Conta
    {
        if (! in_array($tipoConta, Conta::TIPOS, true)) {
            throw new RegraDeNegocioException('Tipo de conta inválido.');
        }

        if ($tipoConta === 'PJ' && $cliente->tipo_cliente !== Cliente::TIPO_PJ) {
            throw new RegraDeNegocioException('Conta PJ exige cliente Pessoa Jurídica.');
        }

        if (in_array($tipoConta, ['CORRENTE', 'POUPANCA', 'SALARIO'], true) && $cliente->tipo_cliente !== Cliente::TIPO_PF) {
            throw new RegraDeNegocioException('Este tipo de conta é exclusivo de Pessoa Física.');
        }

        return Conta::create([
            'user_id' => $user->id,
            'cliente_id' => $cliente->id,
            'numero' => $this->gerarNumeroConta(),
            'tipo_conta' => $tipoConta,
            'agencia' => '0001',
            'status' => Conta::STATUS_PENDENTE,
            'status_abertura' => Conta::ABERTURA_PENDENTE,
            'status_abertura_atualizado_em' => now(),
            'bloqueada' => false,
        ]);
    }

    private function executarKyc(Conta $conta): void
    {
        $conta->update([
            'status_abertura' => Conta::ABERTURA_EM_ANALISE,
            'status_abertura_atualizado_em' => now(),
        ]);

        if (! config('fluxo.kyc.automatico')) {
            return;
        }

        $cliente = $conta->cliente;
        $aprovado = true;

        if ($cliente->ehPessoaFisica() && $cliente->data_nascimento) {
            $idade = Carbon::parse($cliente->data_nascimento)->age;
            $aprovado = $idade >= (int) config('fluxo.kyc.idade_minima');
        }

        if ($aprovado) {
            $conta->update([
                'status_abertura' => Conta::ABERTURA_APROVADA,
                'status_abertura_atualizado_em' => now(),
                'status' => Conta::STATUS_ATIVA,
            ]);

            $cliente->update(['status' => Cliente::STATUS_ATIVO]);
        } else {
            $conta->update([
                'status_abertura' => Conta::ABERTURA_RECUSADA,
                'status_abertura_atualizado_em' => now(),
                'status' => Conta::STATUS_BLOQUEADA,
                'bloqueada' => true,
            ]);

            $cliente->update(['status' => Cliente::STATUS_BLOQUEADO]);
        }
    }

    private function gerarNumeroConta(): string
    {
        do {
            $numero = '0001-'.str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (Conta::where('numero', $numero)->exists());

        return $numero;
    }
}
