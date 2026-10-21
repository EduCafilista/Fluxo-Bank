<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lancamento extends Model
{
    protected $fillable = ['transacao_id', 'conta_id', 'tipo', 'valor_centavos'];

    public function transacao(): BelongsTo
    {
        return $this->belongsTo(Transacao::class);
    }

    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class);
    }
}
