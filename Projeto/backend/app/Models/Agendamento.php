<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Agendamento com máquina de estados e idempotência (RF06/RF25, RN45). */
class Agendamento extends Model
{
    public const TIPO_TED_INTERNA = 'TED_INTERNA';

    public const TIPO_PIX = 'PIX';

    public const TIPO_BOLETO = 'BOLETO';

    public const STATUS_AGENDADO = 'AGENDADO';

    public const STATUS_EXECUTADO = 'EXECUTADO';

    public const STATUS_FALHOU = 'FALHOU';

    public const STATUS_CANCELADO = 'CANCELADO';

    public const STATUS_EXPIRADO = 'EXPIRADO';

    public const MAX_TENTATIVAS = 3;

    protected $table = 'agendamentos';

    protected $fillable = [
        'conta_id', 'transacao_id', 'tipo_operacao', 'valor_centavos',
        'agendado_para', 'payload_operacao', 'status', 'tentativas',
        'chave_idempotencia', 'recorrente', 'proxima_execucao', 'erro',
    ];

    protected function casts(): array
    {
        return [
            'agendado_para' => 'datetime',
            'proxima_execucao' => 'datetime',
            'payload_operacao' => 'array',
            'valor_centavos' => 'integer',
            'tentativas' => 'integer',
            'recorrente' => 'boolean',
        ];
    }

    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class);
    }

    public function transacao(): BelongsTo
    {
        return $this->belongsTo(Transacao::class);
    }

    public function podeExecutar(): bool
    {
        return $this->status === self::STATUS_AGENDADO
            && $this->agendado_para->isPast();
    }
}
