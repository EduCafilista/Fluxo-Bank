<?php

namespace App\Services;

use App\Exceptions\RegraDeNegocioException;
use App\Exceptions\SaldoInsuficienteException;
use App\Models\Conta;
use App\Models\Lancamento;
use App\Models\Transacao;
use Illuminate\Support\Facades\DB;

/**
 * Transferências internas e Pix (RF05 + RN27/RN28 + RNF15/RNF17).
 * A regra fica no Service; o controller só valida entrada e devolve JSON.
 */
class TransferenciaService
{
    public function __construct(
        private LancamentoService $lancamentos,
        private MotorAntifraude $antifraude,
        private LimitePixService $limites,
        private ChavePixService $chaves,
    ) {}

    public function transferir(
        Conta $origem,
        Conta $destino,
        int $valorCentavos,
        ?string $descricao = null,
        string $tipo = Transacao::TIPO_TED_INTERNA,
        ?string $chaveIdempotencia = null,
    ): Transacao {
        // Reenvio do mesmo pedido? Devolve a transação original (RNF17)
        if ($chaveIdempotencia) {
            $existente = Transacao::where('chave_idempotencia', $chaveIdempotencia)->first();

            if ($existente) {
                return $existente;
            }
        }

        $verificacao = $this->antifraude->analisar($origem, $destino, $valorCentavos, $tipo);

        // Reserva o limite antes de tocar no saldo (RN43)
        $consumo = $this->limites->consumirReservado($verificacao['consumo'], $valorCentavos);

        try {
            return DB::transaction(function () use ($origem, $destino, $valorCentavos, $descricao, $tipo, $chaveIdempotencia, $consumo) {
                $origemTravada = Conta::whereKey($origem->id)->lockForUpdate()->firstOrFail();

                if ($origemTravada->saldo() < $valorCentavos) {
                    throw new SaldoInsuficienteException;
                }

                $transacao = $this->lancamentos->transferir(
                    $origemTravada,
                    $destino,
                    $valorCentavos,
                    $descricao,
                    $tipo,
                    $chaveIdempotencia,
                );

                $this->limites->confirmar($consumo, $valorCentavos);

                app(AuditoriaService::class)->registrar(
                    'MOVIMENTACAO',
                    'TRANSFERENCIA_EFETIVADA',
                    [
                        'transacao_id' => $transacao->id,
                        'origem' => $origem->numero,
                        'destino' => $destino->numero,
                        'valor_centavos' => $valorCentavos,
                        'tipo' => $tipo,
                    ],
                    'transacoes',
                    (string) $transacao->id,
                );

                return $transacao->load('lancamentos.conta:id,numero');
            });
        } catch (\Throwable $e) {
            // Falhou: devolve a reserva do limite
            $this->limites->devolverReserva($consumo, $valorCentavos);

            throw $e;
        }
    }

    /** Transferência por chave Pix (RF05). */
    public function transferirPorChavePix(
        Conta $origem,
        string $chaveValor,
        int $valorCentavos,
        ?string $descricao = null,
        ?string $chaveIdempotencia = null,
    ): Transacao {
        $chave = $this->chaves->resolver($chaveValor);

        if ($chave->conta_id === $origem->id) {
            throw new RegraDeNegocioException('Não é possível transferir para uma chave da própria conta.');
        }

        return $this->transferir(
            $origem,
            $chave->conta,
            $valorCentavos,
            $descricao ?? 'Pix para '.$chaveValor,
            Transacao::TIPO_PIX,
            $chaveIdempotencia,
        );
    }

    /** Estorno simples (2 lançamentos invertidos) — usado pelo backoffice. */
    public function estornar(Transacao $transacao, Conta $contaOrigem, Conta $contaDestino): Transacao
    {
        if ($transacao->status === Transacao::STATUS_ESTORNADA) {
            throw new RegraDeNegocioException('Transação já estornada.');
        }

        $valor = (int) $transacao->lancamentos()->where('tipo', Lancamento::TIPO_DEBITO)->sum('valor_centavos');

        $estorno = DB::transaction(function () use ($transacao, $contaOrigem, $contaDestino, $valor) {
            if ($contaDestino->saldo() < $valor) {
                throw new SaldoInsuficienteException('Saldo insuficiente na conta de destino para estornar.');
            }

            $novo = $this->lancamentos->transferir(
                $contaDestino,
                $contaOrigem,
                $valor,
                'Estorno da transação #'.$transacao->id,
                $transacao->tipo,
                'estorno-'.$transacao->id,
            );

            $transacao->update(['status' => Transacao::STATUS_ESTORNADA]);

            return $novo;
        });

        app(AuditoriaService::class)->registrar(
            'MOVIMENTACAO',
            'TRANSFERENCIA_ESTORNADA',
            ['transacao_id' => $transacao->id, 'estorno_id' => $estorno->id],
            'transacoes',
            (string) $transacao->id,
        );

        return $estorno;
    }
}
