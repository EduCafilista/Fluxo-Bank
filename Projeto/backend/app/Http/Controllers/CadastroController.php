<?php

namespace App\Http\Controllers;

use App\Rules\Cpf;
use App\Services\CadastroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CadastroController extends Controller
{
    public function __construct(private CadastroService $cadastro)
    {
    }

    public function store(Request $request): JsonResponse
    {
        // Remove pontos e traço do CPF antes de validar e gravar
        $request->merge([
            'cpf' => preg_replace('/\D/', '', (string) $request->input('cpf')),
        ]);

        $dados = $request->validate([
            'nome' => 'required|string|max:120',
            'cpf' => ['required', 'string', 'size:11', 'unique:clientes,cpf', new Cpf],
            'data_nascimento' => 'required|date|before:today',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|max:72',
        ], [
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
        ]);

        $conta = $this->cadastro->cadastrarPessoaFisica($dados);
        $cliente = $conta->user->cliente;

        return response()->json([
            'mensagem' => 'Cadastro realizado com sucesso.',
            'conta' => $conta->numero,
            'cliente' => [
                'nome' => $cliente->nome,
                'cpf_mascarado' => $cliente->cpfMascarado(),
            ],
        ], 201);
    }
}
