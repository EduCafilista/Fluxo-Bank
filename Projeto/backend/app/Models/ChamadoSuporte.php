<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Chamados de suporte integrados ao perfil do cliente (RF09). */
class ChamadoSuporte extends Model
{
    public const STATUS_ABERTO = 'ABERTO';

    public const STATUS_EM_ATENDIMENTO = 'EM_ATENDIMENTO';

    public const STATUS_RESOLVIDO = 'RESOLVIDO';

    protected $table = 'chamados_suporte';

    protected $fillable = [
        'user_id', 'assunto', 'mensagem', 'status', 'resposta',
        'atendente_id', 'respondido_em',
    ];

    protected function casts(): array
    {
        return ['respondido_em' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function atendente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atendente_id');
    }
}
