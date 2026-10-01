<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transacao extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'transacoes';

    protected $fillable = [
        'tipo',
        'status',
        'chave_idempotencia',
        'criado_em',
    ];

    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class, 'transacao_id');
    }
}
