<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemAssinatura extends Model
{
    protected $table = 'itens_assinatura';

    protected $fillable = ['assinatura_id', 'item_id', 'data_exclusao', 'motivo_exclusao'];

    protected function casts(): array
    {
        return ['data_exclusao' => 'date'];
    }

    public function assinatura(): BelongsTo
    {
        return $this->belongsTo(Assinatura::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
