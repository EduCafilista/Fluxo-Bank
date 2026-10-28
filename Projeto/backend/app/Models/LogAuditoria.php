<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAuditoria extends Model
{
    public const CATEGORIA_ACESSO = 'ACESSO';

    public const CATEGORIA_CONSULTA = 'CONSULTA';

    public const CATEGORIA_MOVIMENTACAO = 'MOVIMENTACAO';

    public const CATEGORIA_SISTEMA = 'SISTEMA';

    public const OPERACAO_INSERT = 'INSERT';

    public const OPERACAO_UPDATE = 'UPDATE';

    public const OPERACAO_DELETE = 'DELETE';

    public $timestamps = false;

    protected $table = 'logs_auditoria';

    protected $fillable = [
        'tabela', 'registro_id', 'operacao', 'categoria', 'usuario_id',
        'request_id', 'ip_address', 'rota', 'metodo', 'status_http',
        'user_agent', 'payload', 'antes', 'depois', 'diff',
        'hash_anterior', 'hash_registro', 'registrado_em',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'antes' => 'array',
            'depois' => 'array',
            'diff' => 'array',
            'registrado_em' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
