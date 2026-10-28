<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssinaturaCartao extends Model
{
    protected $table = 'assinaturas_cartao';

    protected $fillable = [
        'cartao_id', 'conta_id', 'transacao_origem_id', 'identificador_estabelecimento',
        'nome_estabelecimento', 'periodicidade', 'valor_estimado_centavos',
        'ultima_cobranca', 'proxima_cobranca', 'status', 'origem', 'confianca', 'metadados',
    ];

    protected function casts(): array
    {
        return [
            'valor_estimado_centavos' => 'integer',
            'ultima_cobranca' => 'date',
            'proxima_cobranca' => 'date',
            'confianca' => 'float',
            'metadados' => 'array',
        ];
    }

    public function cartao(): BelongsTo
    {
        return $this->belongsTo(Cartao::class);
    }

    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class);
    }
}
