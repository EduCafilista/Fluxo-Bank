<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transacao extends Model
{
    // Obrigatório: sem isso o Laravel procura a tabela "transacaos"
    protected $table = 'transacoes';

    protected $fillable = ['descricao'];

    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class);
    }
}
