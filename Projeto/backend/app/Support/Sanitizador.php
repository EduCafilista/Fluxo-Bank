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

            if (in_array($nome, self::EMAILS, true) && is_scalar($valor)) {
                $saida[$chave] = is_string($valor) ? self::mascararEmail($valor) : $valor;

                continue;
            }

            if (in_array($nome, self::TELEFONES, true) && is_scalar($valor)) {
                $saida[$chave] = is_string($valor) ? self::mascararTelefone($valor) : $valor;

                continue;
            }

            $saida[$chave] = is_array($valor) ? self::limpar($valor) : $valor;
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

        if ($tamanho <= 1) {
            $mascarado = '*';
        } else {
            $mascarado = mb_substr($usuario, 0, 1).'***'.mb_substr($usuario, -1);
        }

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

        if (str_starts_with($digitos, '55') && (strlen($digitos) === 12 || strlen($digitos) === 13)) {
            $digitos = substr($digitos, 2);
        }

        $tamanho = strlen($digitos);

        if ($tamanho === 11) {
            $ddd = substr($digitos, 0, 2);
            $primeiro = substr($digitos, 2, 1);
            $fim = substr($digitos, -4);

            return "({$ddd}) {$primeiro}****-{$fim}";
        }

        if ($tamanho === 10) {
            $ddd = substr($digitos, 0, 2);
            $fim = substr($digitos, -4);

            return "({$ddd}) ****-{$fim}";
        }

        return '***';
    }

    public static function cabecalhos(array $headers): array
    {
        $permitidos = ['user-agent', 'accept', 'content-type', 'x-request-id', 'idempotency-key'];

        return collect($headers)
            ->only($permitidos)
            ->map(fn ($valor) => is_array($valor) ? implode(', ', $valor) : $valor)
            ->all();
    }
}
