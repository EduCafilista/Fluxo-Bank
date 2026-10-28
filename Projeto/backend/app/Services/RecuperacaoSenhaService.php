<?php

namespace App\Services;

use App\Exceptions\RegraDeNegocioException;
use App\Models\ChavePix;
use App\Models\Cliente;
use App\Models\CodigoVerificacao;
use App\Models\Conta;
use App\Models\LogAuditoria;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class RecuperacaoSenhaService
{
    public function __construct(private AuditoriaService $auditoria) {}

    /**
     * Normaliza CPF, CNPJ, e-mail ou telefone e localiza a conta correspondente.
     */
    public function localizarContaPorIdentificador(string $identificador): ?Conta
    {
        $valor = trim($identificador);

        if ($valor === '') {
            return null;
        }

        if (str_contains($valor, '@')) {
            $email = mb_strtolower($valor);

            $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
            if ($user && $user->conta) {
                return $user->conta;
            }

            $chaveEmail = ChavePix::where('tipo_chave', ChavePix::TIPO_EMAIL)
                ->whereRaw('LOWER(valor_chave) = ?', [$email])
                ->where('status', ChavePix::STATUS_ATIVA)
                ->first();

            return $chaveEmail?->conta;
        }

        $digitos = preg_replace('/\D/', '', $valor);

        if ($digitos === '') {
            return null;
        }

        $cliente = Cliente::where('cpf', $digitos)
            ->orWhere('cnpj', $digitos)
            ->first();

        if ($cliente) {
            $conta = $cliente->contas()->orderBy('id')->first();
            if ($conta) {
                return $conta;
            }
        }

        $candidatosTelefone = [$digitos];
        if (str_starts_with($digitos, '55') && strlen($digitos) >= 12) {
            $candidatosTelefone[] = substr($digitos, 2);
        } elseif (strlen($digitos) === 10 || strlen($digitos) === 11) {
            $candidatosTelefone[] = '55'.$digitos;
        }

        $chavePix = ChavePix::whereIn('tipo_chave', [
            ChavePix::TIPO_TELEFONE,
            ChavePix::TIPO_CPF,
            ChavePix::TIPO_CNPJ,
        ])
            ->whereIn('valor_chave', array_unique($candidatosTelefone))
            ->where('status', ChavePix::STATUS_ATIVA)
            ->first();

        return $chavePix?->conta;
    }

    public function solicitarPorIdentificador(string $identificador, ?string $motivo = null): void
    {
        $conta = $this->localizarContaPorIdentificador($identificador);

        if ($conta && $conta->user_id) {
            $this->auditoria->registrar(
                LogAuditoria::CATEGORIA_ACESSO,
                'SENHA_RECUPERACAO_SOLICITADA',
                [
                    'conta_id' => $conta->id,
                    'motivo' => $motivo ? mb_substr($motivo, 0, 190) : null,
                ],
                tabela: 'contas',
                registroId: (string) $conta->id,
                usuarioId: $conta->user_id,
            );

            return;
        }

        $this->auditoria->registrar(
            LogAuditoria::CATEGORIA_ACESSO,
            'SENHA_RECUPERACAO_SOLICITADA',
            ['localizado' => false],
            tabela: 'contas',
        );
    }

    /**
     * @return array{conta_id:int, numero_conta:string, codigo:string, expira_em:string, validade_minutos:int, max_tentativas:int}
     */
    public function gerarCodigoParaConta(Conta $conta, ?User $operador = null): array
    {
        $conta->loadMissing('user');

        if (! $conta->user) {
            throw new RegraDeNegocioException('Conta sem usuário vinculado.', 422);
        }

        $finalidade = CodigoVerificacao::finalidadeRecuperacao($conta->id);
        $validadeMinutos = (int) config('fluxo.recuperacao_senha.validade_minutos', 10);
        $maxTentativas = (int) config('fluxo.recuperacao_senha.max_tentativas', 5);

        CodigoVerificacao::where('user_id', $conta->user_id)
            ->where('finalidade', $finalidade)
            ->whereNull('utilizado_em')
            ->update(['utilizado_em' => now()]);

        $conta->user->tokens()
            ->where('name', 'recuperacao-senha:'.$conta->id)
            ->delete();

        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $registro = CodigoVerificacao::create([
            'user_id' => $conta->user_id,
            'canal' => CodigoVerificacao::CANAL_MANUAL_ADMIN,
            'finalidade' => $finalidade,
            'codigo_hash' => $this->hashCodigo($conta->id, $codigo),
            'expira_em' => now()->addMinutes($validadeMinutos),
            'tentativas' => 0,
            'max_tentativas' => $maxTentativas,
        ]);

        $this->auditoria->registrar(
            LogAuditoria::CATEGORIA_ACESSO,
            'SENHA_RECUPERACAO_CODIGO_GERADO',
            [
                'conta_id' => $conta->id,
                'operador_id' => $operador?->id,
                'expira_em' => $registro->expira_em->toIso8601String(),
            ],
            tabela: 'contas',
            registroId: (string) $conta->id,
            usuarioId: $operador?->id ?? $conta->user_id,
        );

        return [
            'conta_id' => $conta->id,
            'numero_conta' => $conta->numero,
            'codigo' => $codigo,
            'expira_em' => $registro->expira_em->toIso8601String(),
            'validade_minutos' => $validadeMinutos,
            'max_tentativas' => $maxTentativas,
        ];
    }

    /**
     * Valida o código de 6 dígitos a partir do identificador público (CPF, e-mail ou telefone),
     * sem exigir nem expor o ID interno da conta ao cliente.
     *
     * @return array{token_redefinicao:string, expira_em:string}
     */
    public function validarCodigoPorIdentificador(string $identificador, string $codigo): array
    {
        $conta = $this->localizarContaPorIdentificador($identificador);

        if (! $conta || ! $conta->user) {
            $this->auditoria->registrar(
                LogAuditoria::CATEGORIA_ACESSO,
                'SENHA_RECUPERACAO_FALHA',
                ['motivo' => 'identificador_nao_localizado'],
                tabela: 'contas',
            );

            throw new RegraDeNegocioException('Código de recuperação inválido ou expirado.', 401);
        }

        $conta->loadMissing('user');
        $finalidade = CodigoVerificacao::finalidadeRecuperacao($conta->id);

        $registro = CodigoVerificacao::where('user_id', $conta->user_id)
            ->where('finalidade', $finalidade)
            ->latest('id')
            ->first();

        if (! $registro) {
            $this->auditoria->registrar(
                LogAuditoria::CATEGORIA_ACESSO,
                'SENHA_RECUPERACAO_FALHA',
                ['conta_id' => $conta->id, 'motivo' => 'sem_codigo_emitido'],
                tabela: 'contas',
                registroId: (string) $conta->id,
                usuarioId: $conta->user_id,
            );

            throw new RegraDeNegocioException('Código de recuperação inválido ou expirado.', 401);
        }

        if ($registro->utilizado_em !== null) {
            throw new RegraDeNegocioException('Código já utilizado. Solicite um novo código.', 401);
        }

        if ($registro->expira_em->isPast()) {
            throw new RegraDeNegocioException('Código expirado. Solicite um novo código.', 401);
        }

        if ($registro->tentativas >= $registro->max_tentativas) {
            throw new RegraDeNegocioException('Número de tentativas excedido. Solicite um novo código.', 429);
        }

        if (! hash_equals($registro->codigo_hash, $this->hashCodigo($conta->id, $codigo))) {
            $registro->increment('tentativas');
            $registro->refresh();

            $this->auditoria->registrar(
                LogAuditoria::CATEGORIA_ACESSO,
                'SENHA_RECUPERACAO_FALHA',
                [
                    'conta_id' => $conta->id,
                    'tentativas' => $registro->tentativas,
                    'max_tentativas' => $registro->max_tentativas,
                ],
                tabela: 'contas',
                registroId: (string) $conta->id,
                usuarioId: $conta->user_id,
            );

            if ($registro->tentativas >= $registro->max_tentativas) {
                throw new RegraDeNegocioException('Número de tentativas excedido. Solicite um novo código.', 429);
            }

            $restantes = max(0, $registro->max_tentativas - $registro->tentativas);

            throw new RegraDeNegocioException(
                "Código inválido. Tentativas restantes: {$restantes}.",
                401,
            );
        }

        $registro->update(['utilizado_em' => now()]);

        $conta->user->tokens()
            ->where('name', 'recuperacao-senha:'.$conta->id)
            ->delete();

        $validadeToken = (int) config('fluxo.recuperacao_senha.validade_token_minutos', 10);
        $expiraEm = now()->addMinutes($validadeToken);

        $token = $conta->user->createToken(
            'recuperacao-senha:'.$conta->id,
            ['senha:redefinir', 'conta:'.$conta->id],
            $expiraEm,
        );

        $this->auditoria->registrar(
            LogAuditoria::CATEGORIA_ACESSO,
            'SENHA_RECUPERACAO_VALIDADA',
            ['conta_id' => $conta->id],
            tabela: 'contas',
            registroId: (string) $conta->id,
            usuarioId: $conta->user_id,
        );

        return [
            'token_redefinicao' => $token->plainTextToken,
            'expira_em' => $expiraEm->toIso8601String(),
        ];
    }

    public function redefinirSenha(string $tokenRedefinicao, string $novaSenha, ?string $identificador = null): void
    {
        $accessToken = $tokenRedefinicao !== '' ? PersonalAccessToken::findToken($tokenRedefinicao) : null;

        $contaIdToken = null;
        if ($accessToken && is_array($accessToken->abilities)) {
            foreach ($accessToken->abilities as $ability) {
                if (is_string($ability) && str_starts_with($ability, 'conta:')) {
                    $contaIdToken = (int) substr($ability, 6);
                    break;
                }
            }
        }

        $conta = $contaIdToken ? Conta::with('user')->find($contaIdToken) : null;

        if ($identificador !== null && trim($identificador) !== '') {
            $contaInformada = $this->localizarContaPorIdentificador($identificador);
            if (! $contaInformada || ! $conta || (int) $contaInformada->id !== (int) $conta->id) {
                $this->auditoria->registrar(
                    LogAuditoria::CATEGORIA_ACESSO,
                    'SENHA_REDEFINICAO_NEGADA',
                    ['motivo' => 'divergencia_identificador_token'],
                    tabela: 'contas',
                );

                throw new RegraDeNegocioException(
                    'Autorização de redefinição inválida, expirada ou não vinculada a esta conta.',
                    401,
                );
            }
        }

        $tokenValido = $conta
            && $conta->user
            && $accessToken
            && $accessToken->tokenable_type === User::class
            && (int) $accessToken->tokenable_id === (int) $conta->user_id
            && ($accessToken->expires_at === null || $accessToken->expires_at->isFuture())
            && in_array('senha:redefinir', (array) $accessToken->abilities, true)
            && in_array('conta:'.$conta->id, (array) $accessToken->abilities, true);

        if (! $tokenValido) {
            $this->auditoria->registrar(
                LogAuditoria::CATEGORIA_ACESSO,
                'SENHA_REDEFINICAO_NEGADA',
                ['motivo' => 'token_invalido_ou_expirado'],
                tabela: 'contas',
            );

            throw new RegraDeNegocioException(
                'Autorização de redefinição inválida, expirada ou não vinculada a esta conta.',
                401,
            );
        }

        DB::transaction(function () use ($conta, $novaSenha) {
            $user = $conta->user;

            $user->forceFill([
                'password' => $novaSenha,
                'tentativas_login' => 0,
                'bloqueado_ate' => null,
            ])->save();

            CodigoVerificacao::where('user_id', $user->id)
                ->where('finalidade', 'like', CodigoVerificacao::FINALIDADE_RECUPERACAO_PREFIXO.'%')
                ->whereNull('utilizado_em')
                ->update(['utilizado_em' => now()]);

            $user->tokens()->delete();

            DB::table('sessions')->where('user_id', $user->id)->delete();

            $this->auditoria->registrar(
                LogAuditoria::CATEGORIA_ACESSO,
                'SENHA_REDEFINIDA',
                ['conta_id' => $conta->id],
                tabela: 'users',
                registroId: (string) $user->id,
                usuarioId: $user->id,
            );
        });
    }

    private function hashCodigo(int $contaId, string $codigo): string
    {
        return hash_hmac('sha256', $contaId.':'.$codigo, (string) config('app.key', 'fluxo'));
    }
}
