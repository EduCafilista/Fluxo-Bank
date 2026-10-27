<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LancamentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transacao_id' => $this->transacao_id,
            'tipo' => $this->tipo,
            'valor_centavos' => $this->valor_centavos,
            'valor_formatado' => 'R$ '.number_format($this->valor_centavos / 100, 2, ',', '.'),
            'descricao' => $this->descricao ?? $this->whenLoaded('transacao', fn () => $this->transacao?->descricao),
            'categoria' => $this->whenLoaded('transacao', fn () => $this->transacao?->tipo),
            'data' => $this->created_at?->toIso8601String(),
        ];
    }
}
