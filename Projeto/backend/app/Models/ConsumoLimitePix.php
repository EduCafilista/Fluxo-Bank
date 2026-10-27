<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Consumo (utilizado + reservado) por conta, data e janela (RN43). */
class ConsumoLimitePix extends Model
{
    public const JANELA_DIURNA = 'DIURNA';

    public const JANELA_NOTURNA = 'NOTURNA';

    protected $table = 'consumos_limites_pix';

    protected $fillable = [
        'conta_id', 'limite_pix_id', 'data_referencia', 'janela',
        'valor_utilizado', 'quantidade_utilizada', 'valor_reservado', 'quantidade_reservada',
    ];

    protected function casts(): array
    {
        return [
            'data_referencia' => 'date',
            'valor_utilizado' => 'integer',
            'quantidade_utilizada' => 'integer',
            'valor_reservado' => 'integer',
            'quantidade_reservada' => 'integer',
        ];
    }

    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class);
    }

    public function limite(): BelongsTo
    {
        return $this->belongsTo(LimitePix::class, 'limite_pix_id');
    }
}
