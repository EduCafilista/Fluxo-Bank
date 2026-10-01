<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'tipo_cliente',
        'nome',
        'razao_social',
        'cpf',
        'cnpj',
        'data_nascimento',
        'representante_legal',
        'status',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function contas(): HasMany
    {
        return $this->hasMany(Conta::class, 'cliente_id');
    }
}
