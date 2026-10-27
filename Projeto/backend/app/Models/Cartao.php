<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Cartão lógico (RF07/RF12, RN38-RN40): modalidade DÉBITO ou CRÉDITO. */
class Cartao extends Model
{
    public const MODALIDADE_DEBITO = 'DEBITO';

    public const MODALIDADE_CREDITO = 'CREDITO';

    public const STATUS_ATIVO = 'ATIVO';

    public const STATUS_BLOQUEADO = 'BLOQUEADO';

    public const STATUS_CANCELADO = 'CANCELADO';

    protected $table = 'cartoes';

    protected $fillable = [
        'conta_id', 'modalidade', 'numero_mascarado', 'validade', 'cvv_hash', 'status',
        'limite_aprovado_centavos', 'limite_atual_centavos', 'dia_fechamento_fatura',
    ];

    protected $hidden = ['cvv_hash'];

    protected function casts(): array
    {
        return [
            'validade' => 'date',
            'limite_aprovado_centavos' => 'integer',
            'limite_atual_centavos' => 'integer',
            'dia_fechamento_fatura' => 'integer',
        ];
    }

    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class);
    }

    public function fisico(): HasOne
    {
        return $this->hasOne(CartaoFisico::class);
    }

    public function virtual(): HasOne
    {
        return $this->hasOne(CartaoVirtual::class);
    }

    public function avisosViagem(): HasMany
    {
        return $this->hasMany(AvisoViagem::class);
    }

    public function assinaturasCartao(): HasMany
    {
        return $this->hasMany(AssinaturaCartao::class);
    }

    public function ativo(): bool
    {
        return $this->status === self::STATUS_ATIVO;
    }
}
