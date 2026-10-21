<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conta extends Model
{
    protected $fillable = ['user_id', 'numero'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class);
    }

    // Saldo sempre calculado pelos lançamentos (nunca guardado num campo)
    public function saldo(): int
    {
        $creditos = $this->lancamentos()->where('tipo', 'credito')->sum('valor_centavos');
        $debitos = $this->lancamentos()->where('tipo', 'debito')->sum('valor_centavos');

        return (int) ($creditos - $debitos);
    }
}
