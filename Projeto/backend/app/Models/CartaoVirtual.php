<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Instância virtual: sempre originada de um cartão físico ativo (RN38). */
class CartaoVirtual extends Model
{
    protected $table = 'cartoes_virtuais';

    protected $fillable = ['cartao_id', 'cartao_fisico_id', 'gerado_em', 'expira_em'];

    protected function casts(): array
    {
        return ['gerado_em' => 'datetime', 'expira_em' => 'datetime'];
    }

    public function cartao(): BelongsTo
    {
        return $this->belongsTo(Cartao::class);
    }

    public function cartaoFisico(): BelongsTo
    {
        return $this->belongsTo(CartaoFisico::class, 'cartao_fisico_id');
    }
}
