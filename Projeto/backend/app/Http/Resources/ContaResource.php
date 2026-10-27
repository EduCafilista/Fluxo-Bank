<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** RNF08: dados de conta sempre mascarados/formatados na saída. */
class ContaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'agencia' => $this->agencia,
            'tipo_conta' => $this->tipo_conta,
            'status' => $this->status,
            'status_abertura' => $this->status_abertura,
            'status_abertura_atualizado_em' => $this->status_abertura_atualizado_em?->toIso8601String(),
            'bloqueada' => (bool) $this->bloqueada,
            'pode_debitar' => $this->podeDebitar(),
            'titular' => $this->whenLoaded('cliente', fn () => [
                'tipo' => $this->cliente->tipo_cliente,
                'nome' => $this->cliente->nomeExibicao(),
                'documento_mascarado' => $this->cliente->documentoMascarado(),
                'status' => $this->cliente->status,
            ]),
        ];
    }
}
