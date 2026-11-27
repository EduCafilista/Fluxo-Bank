<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CobrancaPix extends Model
{
    protected $table = 'cobrancas_pix';

    protected $fillable = ['conta_id', 'txid', 'hash_payload', 'valor_centavos', 'descricao', 'payload_emv', 'expira_em', 'cancelada_em', 'transacao_id'];

    protected function casts(): array
    {
        return ['valor_centavos' => 'integer', 'expira_em' => 'datetime', 'cancelada_em' => 'datetime', 'payload_emv' => 'encrypted'];
    }

    public function situacao(): string
    {
        if ($this->transacao_id) return 'PAGA';
        if ($this->cancelada_em) return 'CANCELADA';
        return $this->expira_em->isPast() ? 'EXPIRADA' : 'ATIVA';
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
