<?php

namespace App\Services;

use App\Exceptions\RegraDeNegocioException;
use App\Models\ChavePix;
use App\Models\Conta;
use Illuminate\Support\Str;

class ChavePixService
{
    public function cadastrar(Conta $conta, string $tipo, string $valor): ChavePix
    {
        $tipo = mb_strtoupper($tipo);

        if (! in_array($tipo, ChavePix::TIPOS, true)) {
            throw new RegraDeNegocioException('Tipo de chave inválido. Use CPF, CNPJ, EMAIL, TELEFONE ou ALEATORIA.');
        }

        $valor = $this->normalizar($tipo, $valor);

        if ($valor === '') {
            throw new RegraDeNegocioException('Valor da chave não pode ser vazio.');
        }

        if (ChavePix::where('valor_chave', $valor)->exists()) {
            throw new RegraDeNegocioException('Esta chave Pix já está registrada.', 409);
        }

        $limite = 5;

        if ($conta->chavesPix()->where('status', ChavePix::STATUS_ATIVA)->count() >= $limite) {
            throw new RegraDeNegocioException("Cada conta pode ter no máximo {$limite} chaves Pix ativas.");
        }

        $chave = ChavePix::create([
            'conta_id' => $conta->id,
            'tipo_chave' => $tipo,
            'valor_chave' => $valor,
            'status' => ChavePix::STATUS_ATIVA,
            'registrado_em' => now(),
        ]);

        app(AuditoriaService::class)->registrar(
            'MOVIMENTACAO',
            'CHAVE_PIX_CRIADA',
            ['tipo' => $tipo, 'valor' => $valor],
            'chaves_pix',
            (string) $chave->id,
        );

        return $chave;
    }

    public function revogar(ChavePix $chave): ChavePix
    {
        if ($chave->status === ChavePix::STATUS_REVOGADA) {
            throw new RegraDeNegocioException('Chave já revogada.');
        }

        $chave->update(['status' => ChavePix::STATUS_REVOGADA, 'desativada_em' => now()]);

        app(AuditoriaService::class)->registrar(
            'MOVIMENTACAO',
            'CHAVE_PIX_REVOGADA',
            ['tipo' => $chave->tipo_chave],
            'chaves_pix',
            (string) $chave->id,
        );

        return $chave->refresh();
    }

    public function resolver(string $valor): ChavePix
    {
        $chave = ChavePix::where('valor_chave', $valor)
            ->where('status', ChavePix::STATUS_ATIVA)
            ->first();

        if (! $chave) {
            throw new RegraDeNegocioException('Chave Pix não encontrada ou inativa.', 404);
        }

        return $chave;
    }

    private function normalizar(string $tipo, string $valor): string
    {
        $valor = trim($valor);

        return match ($tipo) {
            ChavePix::TIPO_CPF, ChavePix::TIPO_CNPJ => preg_replace('/\D/', '', $valor),
            ChavePix::TIPO_EMAIL => mb_strtolower($valor),
            ChavePix::TIPO_TELEFONE => preg_replace('/\D/', '', $valor),
            ChavePix::TIPO_ALEATORIA => $valor !== '' ? $valor : (string) Str::uuid(),
            default => $valor,
        };
    }
}
