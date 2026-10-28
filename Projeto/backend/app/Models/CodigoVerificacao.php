<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodigoVerificacao extends Model
{
    public const FINALIDADE_LOGIN = 'LOGIN';

    public const FINALIDADE_RETIRADA = 'RETIRADA';

    public const FINALIDADE_ALTERACAO_SENSIVEL = 'ALTERACAO_SENSIVEL';

    public const FINALIDADE_RECUPERACAO_PREFIXO = 'RECUPERACAO:';

    public const CANAL_MANUAL_ADMIN = 'MANUAL_ADMIN';

    public const CANAIS = ['EMAIL', 'SMS', 'PUSH', 'TOTP', 'MANUAL_ADMIN'];

    public static function finalidadeRecuperacao(int $contaId): string
    {
        return self::FINALIDADE_RECUPERACAO_PREFIXO.$contaId;
    }

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
