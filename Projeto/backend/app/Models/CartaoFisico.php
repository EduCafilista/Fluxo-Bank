<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CartaoFisico extends Model
{
    protected $table = 'cartoes_fisicos';

    protected $fillable = [
        'cartao_id', 'data_emissao', 'data_entrega', 'endereco_entrega', 'permite_compras_online',
    ];

    protected function casts(): array
    {
        return [
            'data_emissao' => 'date',
            'data_entrega' => 'date',
            'permite_compras_online' => 'boolean',
        ];
    }

    public function cartao(): BelongsTo
    {
        return $this->belongsTo(Cartao::class);
    }

    public function cartaoVirtual(): HasOne
    {
        return $this->hasOne(CartaoVirtual::class, 'cartao_fisico_id');
    }
}
