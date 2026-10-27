<?php

namespace App\Services;

use App\Exceptions\ContaBloqueadaException;
use App\Exceptions\RegraDeNegocioException;
use App\Models\ConsumoLimitePix;
use App\Models\Conta;
use App\Models\Transacao;

/**
 * Análise de risco antes de efetivar (RF05 + RN27-RN32).
 * Simulação local: não há integração com BACEN/adquirente (fora de escopo).
 */
class MotorAntifraude
{
    public function __construct(private LimitePixService $limites) {}

    /**
     * @return array{janela:string, politica:array, consumo:ConsumoLimitePix}
     */
    public function analisar(Conta $origem, Conta $destino, int $valorCentavos, string $tipo = Transacao::TIPO_TED_INTERNA): array
    {
        // RN34/RN37: conta bloqueada ou com KYC não aprovado não debita
        if (! $origem->podeDebitar()) {
            throw new ContaBloqueadaException(
                $origem->bloqueada
                    ? 'Conta bloqueada: não é possível debitar.'
                    : 'Conta com abertura não aprovada: não é possível debitar.',
            );
        }

        if ($destino->estaEncerrada()) {
            throw new RegraDeNegocioException('A conta de destino está encerrada.');
        }

        if ($origem->id === $destino->id) {
            throw new RegraDeNegocioException('Não é possível transferir para a mesma conta.');
        }

        if ($valorCentavos <= 0) {
            throw new RegraDeNegocioException('O valor deve ser maior que zero.');
        }

        // RN28-RN30 + RN42: limites por transação, dia e janela
        return $this->limites->verificar($origem, $valorCentavos);
    }
}
