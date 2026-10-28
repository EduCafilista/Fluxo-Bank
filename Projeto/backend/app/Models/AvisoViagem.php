<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvisoViagem extends Model
{
    public const STATUS_ATIVO = 'ATIVO';

    public const STATUS_CANCELADO = 'CANCELADO';

    public const STATUS_EXPIRADO = 'EXPIRADO';

    protected $table = 'avisos_viagem';

    protected $fillable = ['conta_id', 'cartao_id', 'data_inicio', 'data_fim', 'localidades', 'status', 'cancelado_em'];

    protected function casts(): array
    {
        return [
            'data_inicio' => 'date',
            'data_fim' => 'date',
            'localidades' => 'array',
            'cancelado_em' => 'datetime',
        ];
    }

    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class);
    }

    public function cartao(): BelongsTo
    {
        return $this->belongsTo(Cartao::class);
    }

    public function cobre(string $localidade, ?string $data = null): bool
    {
        $data = $data ?? now()->toDateString();

        return $this->status === self::STATUS_ATIVO
            && $data >= $this->data_inicio->toDateString()
            && $data <= $this->data_fim->toDateString()
            && in_array(mb_strtoupper($localidade), array_map('mb_strtoupper', $this->localidades ?? []), true);
    }
}
