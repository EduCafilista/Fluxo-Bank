<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CobrancaPix extends Model
{
    protected $table = 'cobrancas_pix';

    protected $fillable = ['conta_id', 'txid', 'hash_payload', 'valor_centavos', 'expira_em', 'transacao_id'];

    protected function casts(): array
    {
        return ['valor_centavos' => 'integer', 'expira_em' => 'datetime'];
    }

    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class);
    }

    public function transacao(): BelongsTo
    {
        return $this->belongsTo(Transacao::class);
    }
}
