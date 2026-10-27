<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Plano contratado pelo cliente (assíncrono ao read model assinaturas_cartao). */
class Assinatura extends Model
{
    public const STATUS_ATIVA = 'ATIVA';

    public const STATUS_SUSPENSA = 'SUSPENSA';

    public const STATUS_CANCELADA = 'CANCELADA';

    protected $table = 'assinaturas';

    protected $fillable = [
        'cliente_id', 'plano_id', 'data_inicio', 'data_fim',
        'dia_cobranca', 'status', 'valor_atual_centavos',
    ];

    protected function casts(): array
    {
        return [
            'data_inicio' => 'date',
            'data_fim' => 'date',
            'dia_cobranca' => 'integer',
            'valor_atual_centavos' => 'integer',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function plano(): BelongsTo
    {
        return $this->belongsTo(Plano::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemAssinatura::class);
    }

    public function cobrancas(): HasMany
    {
        return $this->hasMany(Cobranca::class);
    }

    /** RN22: retirar item nunca faz DELETE, preenche data_exclusao/motivo. */
    public function removerItem(Item $item, string $motivo): void
    {
        $vinculo = $this->itens()->whereNull('data_exclusao')->where('item_id', $item->id)->firstOrFail();

        $vinculo->update(['data_exclusao' => now()->toDateString(), 'motivo_exclusao' => $motivo]);
    }
}
