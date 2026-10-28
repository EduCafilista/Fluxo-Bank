<?php

namespace App\Services;

use App\Exceptions\PartidaDobradaInvalidaException;
use App\Models\Conta;
use App\Models\Lancamento;
use App\Models\Transacao;
use App\Support\ContaInterna;
use Illuminate\Support\Facades\DB;

/**
 * Serviço central de escrituração contábil por partidas dobradas (RN02/RN03).
 */
class LancamentoService
{
    /**
     * @param  array<int, array{conta_id:int, tipo:string, valor_centavos:int, descricao?:string}>  $linhas
     */
    public function lancar(
        string $tipo,
        array $linhas,
        ?string $descricao = null,
        ?string $chaveIdempotencia = null,
        ?string $status = Transacao::STATUS_EFETIVADA,
    ): Transacao {
        $this->validarPartidas($linhas);

        return DB::transaction(function () use ($tipo, $linhas, $descricao, $chaveIdempotencia, $status) {
            if ($chaveIdempotencia !== null) {
                $existente = Transacao::where('chave_idempotencia', $chaveIdempotencia)->first();

                if ($existente) {
                    return $existente;
                }
            }

            $transacao = Transacao::create([
                'tipo' => $tipo,
                'status' => $status,
                'descricao' => $descricao,
                'chave_idempotencia' => $chaveIdempotencia,
            ]);

            $ids = collect($linhas)->pluck('conta_id')->unique()->sort()->values();

            $travadas = Conta::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->pluck('id')->all();

            foreach ($linhas as $linha) {
                if (! in_array($linha['conta_id'], $travadas, true)) {
                    throw new PartidaDobradaInvalidaException('Conta inexistente no lançamento.');
                }

                Lancamento::create([
                    'transacao_id' => $transacao->id,
                    'conta_id' => $linha['conta_id'],
                    'tipo' => $linha['tipo'],
                    'valor_centavos' => $linha['valor_centavos'],
                    'descricao' => $linha['descricao'] ?? $descricao,
                ]);
            }

            $soma = $transacao->somaDosLancamentos();

            if ($soma !== 0) {
                throw new PartidaDobradaInvalidaException(
                    "Lançamentos não fecham em zero (soma = {$soma}). Nada foi gravado.",
                );
            }

            return $transacao;
        });
    }

    public function transferir(
        Conta $origem,
        Conta $destino,
        int $valorCentavos,
        ?string $descricao = null,
        string $tipo = Transacao::TIPO_TED_INTERNA,
        ?string $chaveIdempotencia = null,
    ): Transacao {
        return $this->lancar($tipo, [
            ['conta_id' => $origem->id, 'tipo' => Lancamento::TIPO_DEBITO, 'valor_centavos' => $valorCentavos],
            ['conta_id' => $destino->id, 'tipo' => Lancamento::TIPO_CREDITO, 'valor_centavos' => $valorCentavos],
        ], $descricao, $chaveIdempotencia);
    }

    public function creditar(
        Conta $conta,
        int $valorCentavos,
        string $tipo,
        ?string $descricao = null,
        ?string $chaveIdempotencia = null,
    ): Transacao {
        $interna = ContaInterna::obter();

        return $this->lancar($tipo, [
            ['conta_id' => $interna->id, 'tipo' => Lancamento::TIPO_DEBITO, 'valor_centavos' => $valorCentavos],
            ['conta_id' => $conta->id, 'tipo' => Lancamento::TIPO_CREDITO, 'valor_centavos' => $valorCentavos],
        ], $descricao, $chaveIdempotencia);
    }

    public function debitar(
        Conta $conta,
        int $valorCentavos,
        string $tipo,
        ?string $descricao = null,
        ?string $chaveIdempotencia = null,
    ): Transacao {
        $interna = ContaInterna::obter();

        return $this->lancar($tipo, [
            ['conta_id' => $conta->id, 'tipo' => Lancamento::TIPO_DEBITO, 'valor_centavos' => $valorCentavos],
            ['conta_id' => $interna->id, 'tipo' => Lancamento::TIPO_CREDITO, 'valor_centavos' => $valorCentavos],
        ], $descricao, $chaveIdempotencia);
    }

    private function validarPartidas(array $linhas): void
    {
        if (count($linhas) < 2) {
            throw new PartidaDobradaInvalidaException('Toda transação precisa de pelo menos 2 lançamentos.');
        }

        $soma = 0;

        foreach ($linhas as $linha) {
            $valor = (int) ($linha['valor_centavos'] ?? 0);

            if ($valor <= 0) {
                throw new PartidaDobradaInvalidaException('Cada lançamento precisa ter valor maior que zero.');
            }

            if (! in_array($linha['tipo'], [Lancamento::TIPO_DEBITO, Lancamento::TIPO_CREDITO], true)) {
                throw new PartidaDobradaInvalidaException('Tipo de lançamento inválido (use debito ou credito).');
            }

            $soma += $linha['tipo'] === Lancamento::TIPO_CREDITO ? $valor : -$valor;
        }

        if ($soma !== 0) {
            throw new PartidaDobradaInvalidaException("As partidas não fecham em zero (soma = {$soma}).");
        }
    }
}
