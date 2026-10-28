<?php

namespace App\Services;

use App\Models\Conta;
use App\Models\Lancamento;
use App\Models\Transacao;
use Illuminate\Support\Carbon;

class RelatorioService
{
    public function comprovante(Transacao $transacao): array
    {
        $transacao->load('lancamentos.conta');

        return [
            'transacao_id' => $transacao->id,
            'tipo' => $transacao->tipo,
            'status' => $transacao->status,
            'descricao' => $transacao->descricao,
            'data' => $transacao->created_at?->toIso8601String(),
            'valor_centavos' => (int) $transacao->lancamentos->where('tipo', 'debito')->sum('valor_centavos')
                ?: (int) $transacao->lancamentos->where('tipo', 'credito')->sum('valor_centavos'),
            'partidas_dobradas' => $transacao->partidasDobradasFecham(),
            'lancamentos' => $transacao->lancamentos->map(fn ($l) => [
                'conta' => $l->conta?->numero,
                'tipo' => $l->tipo,
                'valor_centavos' => $l->valor_centavos,
            ])->all(),
            'autenticacao' => strtoupper(substr(hash('sha256', $transacao->id.$transacao->created_at), 0, 16)),
        ];
    }

    public function extratoCategorizado(Conta $conta, ?Carbon $de = null, ?Carbon $ate = null): array
    {
        $de ??= now()->startOfMonth();
        $ate ??= now();

        $porTipo = Lancamento::query()
            ->where('lancamentos.conta_id', $conta->id)
            ->join('transacoes', 'transacoes.id', '=', 'lancamentos.transacao_id')
            ->whereBetween('lancamentos.created_at', [$de->startOfDay(), $ate->endOfDay()])
            ->groupBy('transacoes.tipo', 'lancamentos.tipo')
            ->selectRaw('transacoes.tipo as categoria, lancamentos.tipo as natureza, SUM(lancamentos.valor_centavos) as total, COUNT(*) as quantidade')
            ->get()
            ->map(fn ($linha) => [
                'categoria' => $linha->categoria,
                'natureza' => $linha->natureza,
                'total_centavos' => (int) $linha->total,
                'quantidade' => (int) $linha->quantidade,
            ])
            ->all();

        return [
            'periodo' => ['de' => $de->toDateString(), 'ate' => $ate->toDateString()],
            'saldo_atual_centavos' => $conta->saldo(),
            'categorias' => $porTipo,
        ];
    }
}
