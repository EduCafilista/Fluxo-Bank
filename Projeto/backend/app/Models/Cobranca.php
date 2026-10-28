<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cobranca extends Model
{
    protected $table = 'cobrancas';

    protected $fillable = ['assinatura_id', 'competencia', 'valor_centavos', 'vencimento', 'status'];

    protected function casts(): array
    {
        return ['vencimento' => 'date', 'valor_centavos' => 'integer'];
    }

    public function assinatura(): BelongsTo
    {
        return $this->belongsTo(Assinatura::class);
    }
}
