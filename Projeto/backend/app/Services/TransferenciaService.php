<?php

namespace App\Services;

use App\Exceptions\RegraDeNegocioException;
use App\Exceptions\SaldoInsuficienteException;
use App\Models\Conta;
use App\Models\Lancamento;
use App\Models\Transacao;
use Illuminate\Support\Facades\DB;

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
        return DB::transaction(function () use ($origem, $destino, $valorCentavos, $descricao, $tipo, $chaveIdempotencia) {
            $travadas = Conta::whereIn('id', [$origem->id, $destino->id])
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $origem = $travadas[$origem->id];
            $destino = $travadas[$destino->id];

            if ($chaveIdempotencia) {
                $existente = Transacao::where('chave_idempotencia', $chaveIdempotencia)->first();
                if ($existente) {
                    $debitado = $existente->lancamentos()->where('conta_id', $origem->id)
                        ->where('tipo', Lancamento::TIPO_DEBITO)->where('valor_centavos', $valorCentavos)->exists();
                    $creditado = $existente->lancamentos()->where('conta_id', $destino->id)->where('tipo', Lancamento::TIPO_CREDITO)->exists();

                    if (! $debitado || ! $creditado || $existente->tipo !== $tipo || $existente->descricao !== $descricao) {
                        throw new RegraDeNegocioException('A chave foi usada por outra operação.', 409);
                    }

                    return $existente->load('lancamentos.conta:id,numero');
                }
            }

            $reservado = (int) $origem->consumosLimitePix()->whereDate('data_referencia', '>=', now()->toDateString())->sum('valor_reservado');
            if ($origem->podeDebitar() && $origem->saldo() - $reservado < $valorCentavos) {
                throw new SaldoInsuficienteException;
            }

            $verificacao = $this->antifraude->analisar($origem, $destino, $valorCentavos, $tipo);
            $consumo = $this->limites->consumirReservado($verificacao['consumo'], $valorCentavos);
            $transacao = $this->lancamentos->transferir($origem, $destino, $valorCentavos, $descricao, $tipo, $chaveIdempotencia);
            $this->limites->confirmar($consumo, $valorCentavos);

            app(AuditoriaService::class)->registrar(
                'MOVIMENTACAO', 'TRANSFERENCIA_EFETIVADA', [
                    'transacao_id' => $transacao->id,
                    'origem' => $origem->numero,
                    'destino' => $destino->numero,
                    'valor_centavos' => $valorCentavos,
                    'tipo' => $tipo,
                ], 'transacoes', (string) $transacao->id,
            );

            return $transacao->load('lancamentos.conta:id,numero');
        }, 3);
    }

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
