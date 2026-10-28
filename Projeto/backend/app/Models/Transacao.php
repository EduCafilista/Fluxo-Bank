<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transacao extends Model
{
    protected $table = 'transacoes';

    public const TIPO_PIX = 'PIX';

    public const TIPO_TED_INTERNA = 'TED_INTERNA';

    public const TIPO_BOLETO = 'BOLETO';

    public const TIPO_RETIRADA = 'RETIRADA';

    public const TIPO_DEPOSITO = 'DEPOSITO';

    public const TIPO_ASSINATURA = 'ASSINATURA';

    public const TIPO_QR_CODE = 'QR_CODE';

    public const STATUS_PENDENTE = 'PENDENTE';

    public const STATUS_EFETIVADA = 'EFETIVADA';

    public const STATUS_ESTORNADA = 'ESTORNADA';

    public const STATUS_FALHOU = 'FALHOU';

    protected $fillable = ['tipo', 'status', 'descricao', 'chave_idempotencia'];

    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class);
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class);
    }

    public function somaDosLancamentos(): int
    {
        return (int) $this->lancamentos()
            ->selectRaw("COALESCE(SUM(CASE WHEN tipo = 'credito' THEN valor_centavos ELSE -valor_centavos END), 0) AS total")
            ->value('total');
    }

    public function partidasDobradasFecham(): bool
    {
        return $this->somaDosLancamentos() === 0;
    }
}
