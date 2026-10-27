<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Transação com as duas pontas (partidas dobradas visíveis). */
class TransacaoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'status' => $this->status,
            'descricao' => $this->descricao,
            'chave_idempotencia' => $this->chave_idempotencia,
            'criada_em' => $this->created_at?->toIso8601String(),
            'partidas_dobradas' => [
                'fecham' => $this->partidasDobradasFecham(),
                'soma_centavos' => $this->somaDosLancamentos(),
            ],
            'lancamentos' => $this->whenLoaded('lancamentos', fn () => $this->lancamentos->map(fn ($l) => [
                'conta_id' => $l->conta_id,
                'conta' => $l->relationLoaded('conta') ? $l->conta?->numero : null,
                'tipo' => $l->tipo,
                'valor_centavos' => $l->valor_centavos,
            ])->all()),
            'valor_centavos' => $this->whenLoaded(
                'lancamentos',
                fn () => (int) $this->lancamentos->where('tipo', 'debito')->sum('valor_centavos')
                    ?: (int) $this->lancamentos->where('tipo', 'credito')->sum('valor_centavos'),
            ),
        ];
    }
}
