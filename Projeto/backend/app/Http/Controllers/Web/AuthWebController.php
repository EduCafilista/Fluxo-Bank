<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\CodigoVerificacao;
use App\Models\User;
use App\Rules\Cnpj;
use App\Rules\Cpf;
use App\Services\AuthService;
use App\Services\CadastroService;
use App\Services\RecuperacaoSenhaService;
use App\Services\Servico2FA;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;

class AuthWebController extends Controller
{
    public function loginPage()
    {
        return view('web.auth.login');
    }

    public function login(Request $request, AuthService $servico): RedirectResponse
    {
        $dados = $request->validate([
            'email' => 'required|email', 'password' => 'required|string',
        ]);
        $resultado = $servico->login($dados['email'], $dados['password']);

        if ($resultado['precisa_2fa']) {
            $request->session()->put('fluxo_2fa', [
                'email' => $dados['email'],
                'token' => $resultado['token_temporario'],
                'expira_em' => $resultado['expira_em'] ?? null,
            ]);
            return redirect()->route('web.2fa');
        }

        $this->iniciarSessao($request, User::where('email', $dados['email'])->firstOrFail(), $resultado['token']);

        return redirect()->route('web.home');
    }

    public function twoFactorPage(Request $request)
    {
        if (! $request->session()->has('fluxo_2fa')) {
            return redirect()->route('web.login');
        }
        return view('web.auth.2fa');
    }

    public function twoFactorVerify(Request $request, AuthService $servico): RedirectResponse
    {
        $dados = $request->validate(['codigo' => 'required|digits:6']);
        $pendente = $request->session()->get('fluxo_2fa');
        if (! is_array($pendente) || ! isset($pendente['token'])) {
            return redirect()->route('web.login')->with('erro', 'Entre novamente para verificar sua conta.');
        }
        $token = PersonalAccessToken::findToken($pendente['token']);
        if (! $token || ! $token->can('2fa:pendente') || $token->expires_at?->isPast()) {
            $request->session()->forget('fluxo_2fa');
            return redirect()->route('web.login')->with('erro', 'Código expirado. Faça login novamente.');
        }
        /** @var User $user */
        $user = $token->tokenable;
        $user->withAccessToken($token);
        $resultado = $servico->validarSegundoFator($user, $dados['codigo']);
        $request->session()->forget('fluxo_2fa');
        $this->iniciarSessao($request, $user, $resultado['token']);

        return redirect()->route('web.home')->with('sucesso', 'Verificação concluída.');
    }

    public function registrationPage()
    {
        return view('web.auth.register');
    }

    public function register(Request $request, CadastroService $servico): RedirectResponse
    {
        $tipo = strtoupper((string) $request->input('tipo_cliente', 'PF'));
        $request->merge([
            'tipo_cliente' => $tipo,
            'cpf' => preg_replace('/\D/', '', (string) $request->input('cpf')),
            'cnpj' => preg_replace('/\D/', '', (string) $request->input('cnpj')),
        ]);
        $request->validate(['tipo_cliente' => 'required|in:PF,PJ']);
        $comuns = ['email' => 'required|email|unique:users,email', 'password' => 'required|string|min:8|max:72|confirmed'];
        if ($tipo === Cliente::TIPO_PJ) {
            $dados = $request->validate($comuns + [
                'razao_social' => 'required|string|max:150',
                'cnpj' => ['required', 'size:14', 'unique:clientes,cnpj', new Cnpj],
                'representante_legal' => 'required|string|max:150',
            ]);
            $servico->cadastrarPessoaJuridica($dados + ['nome' => $dados['razao_social']]);
        } else {
            $dados = $request->validate($comuns + [
                'nome' => 'required|string|max:120',
                'cpf' => ['required', 'size:11', 'unique:clientes,cpf', new Cpf],
                'data_nascimento' => 'required|date|before:today',
                'tipo_conta' => 'nullable|in:CORRENTE,POUPANCA,SALARIO',
            ]);
            $servico->cadastrarPessoaFisica($dados);
        }
        return redirect()->route('web.login')->with('sucesso', 'Cadastro recebido. Faça login para acompanhar a abertura da conta.');
    }

    public function recoveryPage()
    {
        return view('web.auth.recovery');
    }

    public function recoveryRequest(Request $request, RecuperacaoSenhaService $servico): RedirectResponse
    {
        $dados = $request->validate(['identificador' => 'required|string|max:255']);
        $servico->solicitarPorIdentificador($dados['identificador']);
        return back()->with('sucesso', 'Caso o cadastro exista, a solicitação foi enviada para análise manual pelo suporte.');
    }

    public function recoveryValidate(Request $request, RecuperacaoSenhaService $servico): RedirectResponse
    {
        $dados = $request->validate(['identificador' => 'required|string|max:255', 'codigo' => 'required|digits:6']);
        $resultado = $servico->validarCodigoPorIdentificador($dados['identificador'], $dados['codigo']);
        $request->session()->put('fluxo_recuperacao', [
            'identificador' => $dados['identificador'],
            'token' => $resultado['token_redefinicao'] ?? $resultado['token'] ?? null,
        ]);
        return back()->with('sucesso', 'Código validado. Cadastre uma nova senha.');
    }

    public function recoveryReset(Request $request, RecuperacaoSenhaService $servico): RedirectResponse
    {
        $dados = $request->validate(['password' => 'required|min:8|max:72|confirmed']);
        $recuperacao = $request->session()->get('fluxo_recuperacao');
        if (! is_array($recuperacao) || empty($recuperacao['token'])) {
            return back()->with('erro', 'Valide o código antes de redefinir a senha.');
        }
        $servico->redefinirSenha($recuperacao['token'], $dados['password'], $recuperacao['identificador']);
        $request->session()->forget('fluxo_recuperacao');
        return redirect()->route('web.login')->with('sucesso', 'Senha alterada. Entre com suas novas credenciais.');
    }

    public function logout(Request $request, AuthService $servico): RedirectResponse
    {
        $token = PersonalAccessToken::findToken((string) $request->session()->get('fluxo_api_token', ''));
        if ($token && $token->tokenable_id === Auth::id()) {
            $user = Auth::user();
            $user->withAccessToken($token);
            $servico->logout($user);
        }
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('web.login')->with('sucesso', 'Sessão encerrada com segurança.');
    }

    private function iniciarSessao(Request $request, User $user, string $token): void
    {
        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->put('fluxo_api_token', $token);
        $request->session()->put('fluxo_ultima_atividade', now()->timestamp);
    }
}
