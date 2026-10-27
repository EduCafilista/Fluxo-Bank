<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Rules\Cnpj;
use App\Rules\Cpf;
use App\Services\CadastroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** RF01/RF10/RN35: cadastro de Pessoa Física e Pessoa Jurídica. */
class CadastroController extends Controller
{
    public function __construct(private CadastroService $cadastro) {}

    public function store(Request $request): JsonResponse
    {
        $tipo = mb_strtoupper((string) $request->input('tipo_cliente', Cliente::TIPO_PF));

        $request->merge([
            'tipo_cliente' => $tipo,
            'cpf' => preg_replace('/\D/', '', (string) $request->input('cpf')),
            'cnpj' => preg_replace('/\D/', '', (string) $request->input('cnpj')),
        ]);

        if ($tipo === Cliente::TIPO_PJ) {
            return $this->cadastrarPj($request);
        }

        $dados = $request->validate([
            'nome' => 'required|string|max:120',
            'cpf' => ['required', 'string', 'size:11', 'unique:clientes,cpf', new Cpf],
            'data_nascimento' => 'required|date|before:today',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|max:72',
            'tipo_conta' => ['nullable', Rule::in(['CORRENTE', 'POUPANCA', 'SALARIO'])],
            'dois_fatores_ativo' => 'nullable|boolean',
        ], $this->mensagens());

        $conta = $this->cadastro->cadastrarPessoaFisica($dados);

        return $this->resposta($conta);
    }

    private function cadastrarPj(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'nome' => 'nullable|string|max:120',
            'razao_social' => 'required|string|max:150',
            'cnpj' => ['required', 'string', 'size:14', 'unique:clientes,cnpj', new Cnpj],
            'representante_legal' => 'required|string|max:150',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|max:72',
            'dois_fatores_ativo' => 'nullable|boolean',
        ], $this->mensagens() + [
            'razao_social.required' => 'Informe a razão social.',
            'cnpj.required' => 'Informe o CNPJ.',
            'cnpj.size' => 'O CNPJ deve ter 14 dígitos.',
            'cnpj.unique' => 'Este CNPJ já está cadastrado.',
            'representante_legal.required' => 'Informe o representante legal.',
        ]);

        $dados['nome'] = $dados['nome'] ?? $dados['razao_social'];

        $conta = $this->cadastro->cadastrarPessoaJuridica($dados);

        return $this->resposta($conta);
    }

    private function resposta($conta): JsonResponse
    {
        $cliente = $conta->cliente;

        return response()->json([
            'mensagem' => $conta->status_abertura === 'APROVADA'
                ? 'Cadastro realizado e aprovado pelo KYC simulado.'
                : 'Cadastro recebido: aguardando análise de KYC.',
            'conta' => $conta->numero,
            'tipo_conta' => $conta->tipo_conta,
            'status_abertura' => $conta->status_abertura,
            'cliente' => [
                'tipo' => $cliente->tipo_cliente,
                'nome' => $cliente->nomeExibicao(),
                'documento_mascarado' => $cliente->documentoMascarado(),
            ],
        ], 201);
    }

    private function mensagens(): array
    {
        return [
            'nome.required' => 'Informe o nome.',
            'nome.max' => 'O nome deve ter no máximo 120 caracteres.',
            'cpf.required' => 'Informe o CPF.',
            'cpf.size' => 'O CPF deve ter 11 dígitos.',
            'cpf.unique' => 'Este CPF já está cadastrado.',
            'data_nascimento.required' => 'Informe a data de nascimento.',
            'data_nascimento.date' => 'Data de nascimento inválida.',
            'data_nascimento.before' => 'A data de nascimento deve ser anterior a hoje.',
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'E-mail inválido.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'password.required' => 'Informe a senha.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.max' => 'A senha deve ter no máximo 72 caracteres.',
            'tipo_conta.in' => 'Tipo de conta inválido.',
        ];
    }
}
