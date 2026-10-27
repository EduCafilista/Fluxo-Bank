<?php

namespace App\Services;

use App\Exceptions\RegraDeNegocioException;
use App\Models\Assinatura;
use App\Models\Cliente;
use App\Models\Cobranca;
use App\Models\Item;
use App\Models\ItemAssinatura;
use App\Models\Plano;

/** Planos/assinaturas do Fluxo + faturamento proporcional (RF - assinaturas). */
class AssinaturaService
{
    public function contratar(Cliente $cliente, Plano $plano, ?int $diaCobranca = null): Assinatura
    {
        if (! $plano->ativo) {
            throw new RegraDeNegocioException('Plano inativo.');
        }

        if ($cliente->assinaturas()->where('status', Assinatura::STATUS_ATIVA)->where('plano_id', $plano->id)->exists()) {
            throw new RegraDeNegocioException('Cliente já possui este plano ativo.', 409);
        }

        $dia = $diaCobranca ?? min((int) now()->day, 28);

        if ($dia < 1 || $dia > 28) {
            throw new RegraDeNegocioException('O dia de cobrança deve ficar entre 1 e 28.');
        }

        $assinatura = Assinatura::create([
            'cliente_id' => $cliente->id,
            'plano_id' => $plano->id,
            'data_inicio' => now()->toDateString(),
            'dia_cobranca' => $dia,
            'status' => Assinatura::STATUS_ATIVA,
            'valor_atual_centavos' => $plano->mensalidade_centavos,
        ]);

        // Fatura proporcional ao dias restantes do mês (faturamento proporcional)
        $this->gerarCobrancaProporcional($assinatura);

        return $assinatura->refresh();
    }

    public function adicionarItem(Assinatura $assinatura, Item $item): ItemAssinatura
    {
        $existente = $assinatura->itens()->where('item_id', $item->id)->whereNull('data_exclusao')->first();

        if ($existente) {
            throw new RegraDeNegocioException('Item já contratado.');
        }

        $vinculo = ItemAssinatura::create([
            'assinatura_id' => $assinatura->id,
            'item_id' => $item->id,
        ]);

        $this->recalcularValor($assinatura);

        return $vinculo;
    }

    /** RN22: retirada é lógica, nunca DELETE do vínculo. */
    public function removerItem(Assinatura $assinatura, Item $item, string $motivo): void
    {
        $assinatura->removerItem($item, $motivo);
        $this->recalcularValor($assinatura);
    }

    public function cancelar(Assinatura $assinatura): Assinatura
    {
        $assinatura->update(['status' => Assinatura::STATUS_CANCELADA, 'data_fim' => now()->toDateString()]);

        return $assinatura->refresh();
    }

    public function gerarCobrancaProporcional(Assinatura $assinatura): Cobranca
    {
        $fimDoMes = now()->endOfMonth();
        $diasRestantes = now()->diffInDays($fimDoMes) + 1;
        $diasNoMes = now()->daysInMonth;

        $valorProporcional = (int) round($assinatura->valor_atual_centavos * ($diasRestantes / $diasNoMes));

        return Cobranca::updateOrCreate(
            [
                'assinatura_id' => $assinatura->id,
                'competencia' => now()->format('Y-m'),
            ],
            [
                'valor_centavos' => $valorProporcional,
                'vencimento' => now()->setDay(min($assinatura->dia_cobranca, now()->daysInMonth))->toDateString(),
                'status' => 'ABERTA',
            ],
        );
    }

    private function recalcularValor(Assinatura $assinatura): void
    {
        $total = $assinatura->plano->mensalidade_centavos;

        $itens = $assinatura->itens()->whereNull('data_exclusao')->with('item')->get();

        foreach ($itens as $vinculo) {
            $total += $vinculo->item->valor_unitario_centavos;
        }

        $assinatura->update(['valor_atual_centavos' => $total]);
    }
}
