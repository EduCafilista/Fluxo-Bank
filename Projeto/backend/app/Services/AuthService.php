<?php

namespace App\Services;

use App\Exceptions\RegraDeNegocioException;
use App\Models\CodigoVerificacao;
use App\Models\LogAuditoria;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    public function __construct(
        private AuditoriaService $auditoria,
        private Servico2FA $doisFatores,
    ) {}

    /**
     * @return array{precisa_2fa:bool, token?:string, expira_em?:string, token_temporario?:string, codigo_teste?:string}
     */
    public function login(string $email, string $senha): array
    {
        $user = User::where('email', $email)->first();

        if ($user && $user->bloqueadoParaLogin()) {
            throw new RegraDeNegocioException(
                'Muitas tentativas. Tente novamente em '.config('fluxo.login.bloqueio_minutos').' minutos.',
                423,
            );
        }

        if (! $user || ! Hash::check($senha, $user->password)) {
            $this->contarFalha($user);
            $this->auditoria->registrar(LogAuditoria::CATEGORIA_ACESSO, 'LOGIN_FALHOU', ['email' => $email]);

            throw new RegraDeNegocioException('Credenciais inválidas.', 401);
        }

        $user->forceFill(['tentativas_login' => 0, 'bloqueado_ate' => null])->save();

        if ($user->dois_fatores_ativo) {
            $codigo = $this->doisFatores->gerar($user, CodigoVerificacao::FINALIDADE_LOGIN);

            $this->auditoria->registrar(
                LogAuditoria::CATEGORIA_ACESSO,
                'LOGIN_2FA_DESAFIO',
                ['email' => $email],
                usuarioId: $user->id,
            );

            $temporario = $user->createToken('2fa-pendente', ['2fa:pendente'], now()->addMinutes(10));

            return [
                'precisa_2fa' => true,
                'token_temporario' => $temporario->plainTextToken,
                'canal' => $codigo['canal'],
                'expira_em' => $codigo['expira_em'],
                'codigo_teste' => app()->environment(['local', 'testing']) ? $codigo['codigo'] : null,
            ];
        }

        $this->auditoria->registrar(LogAuditoria::CATEGORIA_ACESSO, 'LOGIN', ['email' => $email], usuarioId: $user->id);

        return ['precisa_2fa' => false] + $this->emitirToken($user);
    }

    public function validarSegundoFator(User $user, string $codigo): array
    {
        $this->doisFatores->validar($user, $codigo, CodigoVerificacao::FINALIDADE_LOGIN);

        $user->currentAccessToken()?->delete();

        $this->auditoria->registrar(
            LogAuditoria::CATEGORIA_ACESSO,
            'LOGIN_2FA_SUCESSO',
            [],
            usuarioId: $user->id,
        );

        return $this->emitirToken($user);
    }

    public function refresh(User $user): array
    {
        $user->currentAccessToken()?->delete();

        return $this->emitirToken($user);
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();

        $this->auditoria->registrar(LogAuditoria::CATEGORIA_ACESSO, 'LOGOUT', [], usuarioId: $user->id);
    }

    public function ativarDoisFatores(User $user): void
    {
        $user->forceFill(['dois_fatores_ativo' => true])->save();
    }

    public function desativarDoisFatores(User $user): void
    {
        $user->forceFill(['dois_fatores_ativo' => false])->save();
    }

    private function emitirToken(User $user): array
    {
        $nome = 'app-'.Str::lower((string) Str::random(4));
        $token = $user->createToken($nome);

        return [
            'token' => $token->plainTextToken,
            'expira_em' => config('sanctum.expiration')
                ? now()->addMinutes((int) config('sanctum.expiration'))->toIso8601String()
                : null,
            'perfil' => $user->perfil,
        ];
    }

    private function contarFalha(?User $user): void
    {
        if (! $user) {
            return;
        }

        $tentativas = $user->tentativas_login + 1;
        $limite = (int) config('fluxo.login.max_tentativas');

        $user->forceFill([
            'tentativas_login' => $tentativas,
            'bloqueado_ate' => $tentativas >= $limite
                ? now()->addMinutes((int) config('fluxo.login.bloqueio_minutos'))
                : null,
        ])->save();
    }
}
