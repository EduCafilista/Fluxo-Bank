<?php

namespace App\Support;

/**
 * Remove/mascara dados sensíveis antes de gravar em log (RF-SEG01, RNF13, LGPD).
 * Nunca gravar senha, token, CVV ou código de verificação — nem em auditoria.
 */
class Sanitizador
{
    /** Campos cujo valor é sempre descartado. */
    private const PROIBIDOS = [
        'password', 'password_confirmation', 'senha', 'current_password',
        'token', 'access_token', 'refresh_token', 'token_temporario',
        'authorization', 'cvv', 'cvv_hash', 'codigo', 'codigo_hash',
        'secret', 'api_key',
    ];

    /** Campos que são mascarados (mostram só o final). */
    private const MASCARADOS = ['cpf', 'cnpj', 'numero_cartao', 'cartao'];

    public static function limpar(mixed $dado): mixed
    {
        if (! is_array($dado)) {
            return $dado;
        }

        $saida = [];

        foreach ($dado as $chave => $valor) {
            $nome = mb_strtolower((string) $chave);

            if (in_array($nome, self::PROIBIDOS, true)) {
                $saida[$chave] = '[REMOVIDO]';

                continue;
            }

            if (in_array($nome, self::MASCARADOS, true) && is_scalar($valor)) {
                $digitos = preg_replace('/\D/', '', (string) $valor);
                $saida[$chave] = '***'.substr($digitos, -2);

                continue;
            }

            $saida[$chave] = is_array($valor) ? self::limpar($valor) : $valor;
        }

        return $saida;
    }

    /** Cabeçalhos que podem ser registrados, também sanitizados. */
    public static function cabecalhos(array $headers): array
    {
        $permitidos = ['user-agent', 'accept', 'content-type', 'x-request-id', 'idempotency-key'];

        return collect($headers)
            ->only($permitidos)
            ->map(fn ($valor) => is_array($valor) ? implode(', ', $valor) : $valor)
            ->all();
    }
}
