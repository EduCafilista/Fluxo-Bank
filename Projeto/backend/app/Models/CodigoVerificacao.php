<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Desafio de 2FA (RF02, RN19/RN20). Guarda apenas o hash do código. */
class CodigoVerificacao extends Model
{
    public const FINALIDADE_LOGIN = 'LOGIN';

    public const FINALIDADE_RETIRADA = 'RETIRADA';

    public const FINALIDADE_ALTERACAO_SENSIVEL = 'ALTERACAO_SENSIVEL';

    public const CANAIS = ['EMAIL', 'SMS', 'PUSH', 'TOTP'];

    protected $table = 'codigos_verificacao';

    protected $fillable = [
        'user_id', 'canal', 'finalidade', 'codigo_hash',
        'expira_em', 'tentativas', 'max_tentativas', 'utilizado_em',
    ];

    protected function casts(): array
    {
        return [
            'expira_em' => 'datetime',
            'utilizado_em' => 'datetime',
            'tentativas' => 'integer',
            'max_tentativas' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vigente(): bool
    {
        return $this->utilizado_em === null
            && $this->expira_em->isFuture()
            && $this->tentativas < $this->max_tentativas;
    }
}
