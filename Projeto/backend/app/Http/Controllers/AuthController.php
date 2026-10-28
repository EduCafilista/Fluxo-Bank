<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Services\Servico2FA;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $auth,
        private Servico2FA $doisFatores,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'E-mail inválido.',
            'password.required' => 'Informe a senha.',
        ]);

        return response()->json($this->auth->login($dados['email'], $dados['password']));
    }

    public function validarDoisFatores(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'codigo' => 'required|digits:6',
        ], [
            'codigo.required' => 'Informe o código recebido.',
            'codigo.digits' => 'O código tem 6 dígitos.',
        ]);

        return response()->json([
            'mensagem' => 'Verificação concluída.',
        ] + $this->auth->validarSegundoFator($request->user(), $dados['codigo']));
    }

    public function novoDesafio(Request $request): JsonResponse
    {
        $codigo = $this->doisFatores->gerar($request->user(), 'LOGIN');

        return response()->json([
            'canal' => $codigo['canal'],
            'expira_em' => $codigo['expira_em'],
            'codigo_teste' => app()->environment(['local', 'testing']) ? $codigo['codigo'] : null,
        ]);
    }

    public function ativarDoisFatores(Request $request): JsonResponse
    {
        $this->auth->ativarDoisFatores($request->user());

        return response()->json(['mensagem' => 'Verificação em dois fatores ativada.']);
    }

    public function desativarDoisFatores(Request $request): JsonResponse
    {
        $this->auth->desativarDoisFatores($request->user());

        return response()->json(['mensagem' => 'Verificação em dois fatores desativada.']);
    }

    public function refresh(Request $request): JsonResponse
    {
        return response()->json($this->auth->refresh($request->user()));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->user());

        return response()->json(['mensagem' => 'Sessão encerrada.']);
    }

    public function logoutTodos(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(['mensagem' => 'Todos os dispositivos foram desconectados.']);
    }

    public function eu(Request $request): JsonResponse
    {
        $user = $request->user()->load('cliente');

        return response()->json([
            'id' => $user->id,
            'nome' => $user->name,
            'email' => $user->email,
            'perfil' => $user->perfil,
            'dois_fatores_ativo' => $user->dois_fatores_ativo,
            'cliente' => $user->cliente ? [
                'tipo' => $user->cliente->tipo_cliente,
                'nome' => $user->cliente->nomeExibicao(),
                'documento_mascarado' => $user->cliente->documentoMascarado(),
                'status' => $user->cliente->status,
            ] : null,
            'contas' => $user->contas()->get(['id', 'numero', 'tipo_conta', 'status', 'bloqueada']),
        ]);
    }
}
