<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LimitePix extends Model
{
    protected $table = 'limites_pix';

    protected $fillable = [
        'conta_id', 'limite_por_transacao', 'limite_diario', 'quantidade_diaria',
        'limite_noturno_por_transacao', 'limite_noturno_diario', 'quantidade_noturna',
        'limite_sem_chave_por_transacao', 'limite_sem_chave_diario',
        'hora_inicio_noturno', 'hora_fim_noturno', 'ativo', 'vigente_desde', 'vigente_ate',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'limite_por_transacao' => 'integer',
            'limite_diario' => 'integer',
            'quantidade_diaria' => 'integer',
            'limite_noturno_por_transacao' => 'integer',
            'limite_noturno_diario' => 'integer',
            'quantidade_noturna' => 'integer',
            'limite_sem_chave_por_transacao' => 'integer',
            'limite_sem_chave_diario' => 'integer',
            'vigente_desde' => 'datetime',
            'vigente_ate' => 'datetime',
        ];
    }

    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class);
    }

    public function consumos(): HasMany
    {
        return $this->hasMany(ConsumoLimitePix::class);
    }
}
