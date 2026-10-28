<?php

namespace App\Http\Controllers;

use App\Services\RecuperacaoSenhaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecuperacaoSenhaController extends Controller
{
    public function __construct(private RecuperacaoSenhaService $recuperacao) {}

    public function solicitar(Request $request): JsonResponse
    {
        $this->normalizarIdentificadorNaRequisicao($request);

        $dados = $request->validate([
            'identificador' => 'required|string|max:255',
            'motivo' => 'nullable|string|max:190',
        ], [
            'identificador.required' => 'Informe seu CPF, e-mail ou telefone cadastrado.',
        ]);

        $this->recuperacao->solicitarPorIdentificador($dados['identificador'], $dados['motivo'] ?? null);

        return response()->json([
            'mensagem' => 'Se os dados informados corresponderem a um cadastro ativo, a solicitação foi registrada para verificação manual pelo suporte.',
        ]);
    }

    public function validar(Request $request): JsonResponse
    {
        $this->normalizarIdentificadorNaRequisicao($request);

        $dados = $request->validate([
            'identificador' => 'required|string|max:255',
            'codigo' => ['required', 'string', 'regex:/^\d{6}$/'],
        ], [
            'identificador.required' => 'Informe seu CPF, e-mail ou telefone cadastrado.',
            'codigo.required' => 'Informe o código de 6 dígitos.',
            'codigo.regex' => 'O código deve conter exatamente 6 dígitos numéricos.',
        ]);

        $resultado = $this->recuperacao->validarCodigoPorIdentificador($dados['identificador'], $dados['codigo']);

        return response()->json([
            'mensagem' => 'Código validado. Utilize a autorização temporária para cadastrar a nova senha.',
        ] + $resultado);
    }

    public function redefinir(Request $request): JsonResponse
    {
        $this->normalizarIdentificadorNaRequisicao($request);

        $senhaInformada = $request->input('password') ?? $request->input('nova_senha');
        $confirmacao = $request->input('password_confirmation') ?? $request->input('nova_senha_confirmation');

        if ($senhaInformada !== null && ! $request->has('password')) {
            $request->merge(['password' => $senhaInformada]);
        }

        if ($confirmacao !== null && ! $request->has('password_confirmation')) {
            $request->merge(['password_confirmation' => $confirmacao]);
        }

        $regras = [
            'identificador' => 'nullable|string|max:255',
            'password' => 'required|string|min:8|max:72',
            'token_redefinicao' => 'nullable|string',
        ];

        if ($request->has('password_confirmation')) {
            $regras['password_confirmation'] = 'required|string|same:password';
        }

        $dados = $request->validate($regras, [
            'password.required' => 'Informe a nova senha.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.max' => 'A senha deve ter no máximo 72 caracteres.',
            'password_confirmation.same' => 'A confirmação da senha não confere.',
        ]);

        $token = (string) ($dados['token_redefinicao'] ?? $request->bearerToken() ?? '');

        $this->recuperacao->redefinirSenha($token, $dados['password'], $dados['identificador'] ?? null);

        return response()->json([
            'mensagem' => 'Senha redefinida com sucesso. As sessões e os tokens anteriores foram revogados.',
        ]);
    }

    private function normalizarIdentificadorNaRequisicao(Request $request): void
    {
        if ($request->filled('identificador')) {
            return;
        }

        foreach (['cpf', 'email', 'telefone', 'documento'] as $campo) {
            if ($request->filled($campo)) {
                $request->merge(['identificador' => (string) $request->input($campo)]);

                return;
            }
        }
    }
}
