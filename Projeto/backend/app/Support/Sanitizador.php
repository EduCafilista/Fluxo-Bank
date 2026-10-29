<?php

namespace App\Support;

/**
 * Sanitização de dados sensíveis para logs e trilhas de auditoria (RNF13/LGPD).
 */
class Sanitizador
{
    private const PROIBIDOS = [
        'password', 'password_confirmation', 'senha', 'nova_senha', 'nova_senha_confirmation',
        'current_password', 'token', 'access_token', 'refresh_token', 'token_temporario',
        'token_redefinicao', 'authorization', 'cvv', 'cvv_hash', 'codigo', 'codigo_hash',
        'secret', 'api_key',
    ];

    private const MASCARADOS = ['cpf', 'cnpj', 'numero_cartao', 'cartao'];

    private const EMAILS = ['email', 'e_mail', 'chave_email'];

    private const TELEFONES = ['telefone', 'celular', 'phone', 'telefone_contato', 'chave_telefone'];

    public static function limpar(mixed $dado): mixed
    {
        if (! is_array($dado)) {
            return $dado;
        }

        $saida = [];

        foreach ($dado as $chave => $valor) {
            $saida[$chave] = self::sanitizarValor((string) $chave, $valor);
        }

        return $saida;
    }

    public static function mascararEmail(string $email): string
    {
        $email = trim($email);

        if ($email === '') {
            return '';
        }

        if (! str_contains($email, '@')) {
            return '***';
        }

        [$usuario, $dominio] = explode('@', $email, 2);
        $tamanho = mb_strlen($usuario);

        $mascarado = ($tamanho <= 1)
            ? '*'
            : mb_substr($usuario, 0, 1).'***'.mb_substr($usuario, -1);

        return $mascarado.'@'.$dominio;
    }

    public static function mascararTelefone(string $telefone): string
    {
        $telefone = trim($telefone);

        if ($telefone === '') {
            return '';
        }

        $digitos = preg_replace('/\D/', '', $telefone);

        if ($digitos === '') {
            return '';
        }

        if (str_starts_with($digitos, '55') && in_array(strlen($digitos), [12, 13], true)) {
            $digitos = substr($digitos, 2);
        }

        return match (strlen($digitos)) {
            11 => sprintf('(%s) %s****-%s', substr($digitos, 0, 2), substr($digitos, 2, 1), substr($digitos, -4)),
            10 => sprintf('(%s) ****-%s', substr($digitos, 0, 2), substr($digitos, -4)),
            default => '***',
        };
    }

    public static function cabecalhos(array $headers): array
    {
        $permitidos = ['user-agent', 'accept', 'content-type', 'x-request-id', 'idempotency-key'];

        return collect($headers)
            ->only($permitidos)
            ->map(fn ($valor) => is_array($valor) ? implode(', ', $valor) : $valor)
            ->all();
    }

    private static function sanitizarValor(string $chave, mixed $valor): mixed
    {
        $nome = mb_strtolower($chave);

        if (in_array($nome, self::PROIBIDOS, true)) {
            return '[REMOVIDO]';
        }

        if (is_scalar($valor)) {
            if (in_array($nome, self::MASCARADOS, true)) {
                return self::mascararDocumentoOuCartao($valor);
            }

            if (in_array($nome, self::EMAILS, true) && is_string($valor)) {
                return self::mascararEmail($valor);
            }

            if (in_array($nome, self::TELEFONES, true) && is_string($valor)) {
                return self::mascararTelefone($valor);
            }
        }

        return is_array($valor) ? self::limpar($valor) : $valor;
    }

    private static function mascararDocumentoOuCartao(mixed $valor): string
    {
        $digitos = preg_replace('/\D/', '', (string) $valor);

        return '***'.substr($digitos, -2);
    }
}
