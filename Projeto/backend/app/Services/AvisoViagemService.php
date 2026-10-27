<?php

namespace App\Services;

use App\Exceptions\RegraDeNegocioException;
use App\Models\AvisoViagem;
use App\Models\Cartao;
use App\Models\Conta;

/** Aviso de viagem por cartão (RF24 + RN44). */
class AvisoViagemService
{
    public function criar(Conta $conta, Cartao $cartao, array $localidades, string $inicio, string $fim): AvisoViagem
    {
        if ($cartao->conta_id !== $conta->id) {
            throw new RegraDeNegocioException('Cartão não pertence a esta conta.', 403);
        }

        if ($inicio > $fim) {
            throw new RegraDeNegocioException('A data final precisa ser depois da inicial.');
        }

        if (empty($localidades)) {
            throw new RegraDeNegocioException('Informe ao menos uma localidade.');
        }

        return AvisoViagem::create([
            'conta_id' => $conta->id,
            'cartao_id' => $cartao->id,   // RN44: obrigatório
            'data_inicio' => $inicio,
            'data_fim' => $fim,
            'localidades' => array_values(array_map('mb_strtoupper', $localidades)),
            'status' => AvisoViagem::STATUS_ATIVO,
        ]);
    }

    public function cancelar(AvisoViagem $aviso): AvisoViagem
    {
        $aviso->update(['status' => AvisoViagem::STATUS_CANCELADO, 'cancelado_em' => now()]);

        return $aviso->refresh();
    }

    /** Consulta usada pelo antifraude de localização (RN32/RN44). */
    public function existeAvisoAtivo(Cartao $cartao, string $localidade): bool
    {
        return $cartao->avisosViagem()
            ->where('status', AvisoViagem::STATUS_ATIVO)
            ->get()
            ->contains(fn (AvisoViagem $aviso) => $aviso->cobre($localidade));
    }
}
