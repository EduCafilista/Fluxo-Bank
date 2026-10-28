<?php

namespace App\Support;

/**
 * Gerador de payload Pix no padrão EMV/BR Code com cálculo de CRC16 (CCITT-FALSE, polinômio 0x1021).
 */
class Emv
{
    public static function pixPayload(string $chave, int $valorCentavos, string $txid, ?string $nome = null, ?string $cidade = null): string
    {
        $nome = self::normalizar($nome ?? config('fluxo.pix.nome_recebedor'));
        $cidade = self::normalizar($cidade ?? config('fluxo.pix.cidade_recebedor'));
        $txid = preg_replace('/[^A-Za-z0-9*]/', '', $txid) ?: '***';
        $txid = substr($txid, 0, 25);

        $mai = self::campo(0, 'BR.GOV.BCB.PIX')
            .self::campo(1, $chave);

        $payload = self::campo(0, '01')
            .self::campo(26, $mai)
            .self::campo(52, '0000')
            .self::campo(53, '986')
            .self::campo(54, self::valor($valorCentavos))
            .self::campo(58, 'BR')
            .self::campo(59, substr($nome, 0, 25))
            .self::campo(60, substr($cidade, 0, 15))
            .self::campo(62, self::campo(5, $txid))
            .'6304';

        return $payload.self::crc16($payload);
    }

    private static function campo(int $id, string $valor): string
    {
        return str_pad((string) $id, 2, '0', STR_PAD_LEFT)
            .str_pad((string) strlen($valor), 2, '0', STR_PAD_LEFT)
            .$valor;
    }

    private static function valor(int $centavos): string
    {
        return $centavos > 0 ? number_format($centavos / 100, 2, '.', '') : '';
    }

    private static function normalizar(?string $texto): string
    {
        $texto = strtr((string) $texto, [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'ê' => 'e', 'è' => 'e', 'í' => 'i', 'ó' => 'o',
            'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ü' => 'u', 'ç' => 'c',
            'Á' => 'A', 'À' => 'A', 'Ã' => 'A', 'Â' => 'A', 'É' => 'E',
            'Ê' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O',
            'Ú' => 'U', 'Ç' => 'C',
        ]);

        return strtoupper(trim(preg_replace('/[^A-Za-z0-9 ]/', ' ', $texto)));
    }

    public static function crc16(string $payload): string
    {
        $crc = 0xFFFF;

        for ($i = 0, $len = strlen($payload); $i < $len; $i++) {
            $crc ^= ord($payload[$i]) << 8;

            for ($bit = 0; $bit < 8; $bit++) {
                $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) & 0xFFFF : ($crc << 1) & 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
