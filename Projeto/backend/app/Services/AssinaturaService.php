<?php

namespace App\Services;

use App\Exceptions\RegraDeNegocioException;
use App\Models\Assinatura;
use App\Models\Cliente;
use App\Models\Cobranca;
use App\Models\Item;
use App\Models\ItemAssinatura;
use App\Models\Plano;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AssinaturaService
{
    public function contratar(Cliente $cliente, Plano $plano, ?int $diaCobranca = null): Assinatura
    {
        if (! $plano->ativo) {
            throw new RegraDeNegocioException('Plano inativo.');
        }

        $dia = $diaCobranca ?? min((int) now()->day, 28);

        if ($dia < 1 || $dia > 28) {
            throw new RegraDeNegocioException('O dia de cobrança deve ficar entre 1 e 28.');
        }

        return DB::transaction(function () use ($cliente, $plano, $dia) {
            $cliente = Cliente::query()->lockForUpdate()->findOrFail($cliente->id);
            if ($cliente->status === Cliente::STATUS_ENCERRADO) {
                throw new RegraDeNegocioException('Cadastro encerrado não pode contratar planos.');
            }

            if ($cliente->assinaturas()->where('status', Assinatura::STATUS_ATIVA)->where('plano_id', $plano->id)->exists()) {
                throw new RegraDeNegocioException('Cliente já possui este plano ativo.', 409);
            }

            $assinatura = Assinatura::create([
                'cliente_id' => $cliente->id,
                'plano_id' => $plano->id,
                'data_inicio' => now()->toDateString(),
                'dia_cobranca' => $dia,
                'status' => Assinatura::STATUS_ATIVA,
                'valor_atual_centavos' => $plano->mensalidade_centavos,
            ]);

            $this->gerarCobrancaProporcional($assinatura);

            return $assinatura;
        });
    }

    public function adicionarItem(Assinatura $assinatura, Item $item): ItemAssinatura
    {
        return DB::transaction(function () use ($assinatura, $item) {
            $assinatura = $this->travarAssinaturaAtiva($assinatura);
            if ($assinatura->itens()->where('item_id', $item->id)->whereNull('data_exclusao')->exists()) {
                throw new RegraDeNegocioException('Item já contratado.');
            }

            $vinculo = $assinatura->itens()->create(['item_id' => $item->id]);
            $this->recalcularValor($assinatura);
            $this->gerarCobrancaProporcional($assinatura);

            return $vinculo;
        });
    }

    public function removerItem(Assinatura $assinatura, Item $item, string $motivo): void
    {
        DB::transaction(function () use ($assinatura, $item, $motivo) {
            $assinatura = $this->travarAssinaturaAtiva($assinatura);
            $assinatura->removerItem($item, $motivo);
            $this->recalcularValor($assinatura);
            $this->gerarCobrancaProporcional($assinatura);
        });
    }

    public function cancelar(Assinatura $assinatura): Assinatura
    {
        return DB::transaction(function () use ($assinatura) {
            $assinatura = $this->travarAssinaturaAtiva($assinatura);
            $assinatura->update(['status' => Assinatura::STATUS_CANCELADA, 'data_fim' => now()->toDateString()]);
            $this->gerarCobrancaProporcional($assinatura);

            return $assinatura;
        });
    }

    public function gerarCobrancaProporcional(Assinatura $assinatura): Cobranca
    {
        $competencia = now()->startOfMonth();
        $fimMes = $competencia->copy()->endOfMonth()->startOfDay();
        $inicioPlano = $assinatura->data_inicio->max($competencia);
        $fimPlano = $assinatura->data_fim
            ? $assinatura->data_fim->copy()->subDay()->min($fimMes)
            : $fimMes;
        $valorDias = $assinatura->plano->mensalidade_centavos * $this->diasAtivos($inicioPlano, $fimPlano);

        foreach ($assinatura->itens()->with('item')->get() as $vinculo) {
            $inicio = $vinculo->created_at?->copy()->startOfDay()->max($inicioPlano) ?? $inicioPlano;
            $fim = $vinculo->data_exclusao
                ? $vinculo->data_exclusao->copy()->subDay()->min($fimPlano)
                : $fimPlano;
            $valorDias += $vinculo->item->valor_unitario_centavos * $this->diasAtivos($inicio, $fim);
        }

        $criterio = ['competencia' => $competencia->format('Y-m')];
        $vencimento = $competencia->copy()->day($assinatura->dia_cobranca)->max($inicioPlano);
        $cobranca = $assinatura->cobrancas()->firstOrNew($criterio);

        if (! $cobranca->exists || $cobranca->status === 'ABERTA') {
            $cobranca->fill([
                'valor_centavos' => intdiv($valorDias + intdiv($competencia->daysInMonth, 2), $competencia->daysInMonth),
                'vencimento' => $vencimento->toDateString(),
                'status' => 'ABERTA',
            ])->save();
        }

        return $cobranca;
    }

    private function diasAtivos(Carbon $inicio, Carbon $fim): int
    {
        return $inicio->lte($fim) ? (int) $inicio->diffInDays($fim) + 1 : 0;
    }

    private function travarAssinaturaAtiva(Assinatura $assinatura): Assinatura
    {
        $assinatura = Assinatura::query()->lockForUpdate()->findOrFail($assinatura->id);
        if ($assinatura->status !== Assinatura::STATUS_ATIVA) {
            throw new RegraDeNegocioException('A assinatura não está ativa.');
        }

        return $assinatura;
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
