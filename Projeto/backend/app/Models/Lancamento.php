<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lancamento extends Model
{
    public const TIPO_DEBITO = 'debito';

    public const TIPO_CREDITO = 'credito';

    protected $fillable = ['transacao_id', 'conta_id', 'tipo', 'valor_centavos', 'descricao'];

    protected function casts(): array
    {
        return ['valor_centavos' => 'integer'];
    }

    public function transacao(): BelongsTo
    {
        return $this->belongsTo(Transacao::class);
    }

    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class);
    }
}
