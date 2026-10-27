<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Catálogo de planos do Fluxo (RF - assinaturas). */
class Plano extends Model
{
    protected $table = 'planos';

    protected $fillable = ['nome', 'mensalidade_centavos', 'limite_retiradas', 'ativo'];

    protected function casts(): array
    {
        return ['mensalidade_centavos' => 'integer', 'limite_retiradas' => 'integer', 'ativo' => 'boolean'];
    }

    public function assinaturas(): HasMany
    {
        return $this->hasMany(Assinatura::class);
    }
}
