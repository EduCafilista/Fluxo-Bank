<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conta extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'cliente_id',
        'tipo_conta',
        'agencia',
        'numero',
        'status',
        'bloqueada',
        'cnpj_vinculado',
        'aniversario_rendimento',
        'restricao_saque',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class, 'conta_id');
    }

    public function chavesPix(): HasMany
    {
        return $this->hasMany(ChavePix::class, 'conta_id');
    }
}
