<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Conta extends Model
{
    use HasFactory;

    public const STATUS_PENDENTE = 'PENDENTE';

    public const STATUS_ATIVA = 'ATIVA';

    public const STATUS_BLOQUEADA = 'BLOQUEADA';

    public const STATUS_ENCERRADA = 'ENCERRADA';

    public const ABERTURA_PENDENTE = 'PENDENTE';

    public const ABERTURA_EM_ANALISE = 'EM_ANALISE';

    public const ABERTURA_APROVADA = 'APROVADA';

    public const ABERTURA_RECUSADA = 'RECUSADA';

    public const TIPOS = ['CORRENTE', 'POUPANCA', 'SALARIO', 'PJ', 'INSTITUCIONAL'];

    protected $fillable = [
        'user_id', 'cliente_id', 'numero', 'tipo_conta', 'agencia',
        'status', 'status_abertura', 'status_abertura_atualizado_em',
        'bloqueada', 'restricao_saque', 'aniversario_rendimento',
    ];

    protected function casts(): array
    {
        return [
            'bloqueada' => 'boolean',
            'restricao_saque' => 'boolean',
            'status_abertura_atualizado_em' => 'datetime',
            'aniversario_rendimento' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class);
    }

    public function chavesPix(): HasMany
    {
        return $this->hasMany(ChavePix::class);
    }

    public function cartoes(): HasMany
    {
        return $this->hasMany(Cartao::class);
    }

    public function limitesPix(): HasMany
    {
        return $this->hasMany(LimitePix::class);
    }

    public function consumosLimitePix(): HasMany
    {
        return $this->hasMany(ConsumoLimitePix::class);
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class);
    }

    public function avisosViagem(): HasMany
    {
        return $this->hasMany(AvisoViagem::class);
    }

    public function limitePixVigente(): HasOne
    {
        return $this->hasOne(LimitePix::class)->where('ativo', true)->latestOfMany();
    }

    public function chavePixAtiva(): HasOne
    {
        return $this->hasOne(ChavePix::class)->where('status', ChavePix::STATUS_ATIVA)->latestOfMany();
    }

    /**
     * Saldo derivado dos lançamentos (RN02/RF03) em UMA query (RNF01).
     * Nunca existe coluna de saldo na tabela contas.
     */
    public function saldo(): int
    {
        $soma = $this->lancamentos()
            ->selectRaw("COALESCE(SUM(CASE WHEN tipo = 'credito' THEN valor_centavos ELSE -valor_centavos END), 0) AS total")
            ->value('total');

        return (int) $soma;
    }

    /** Saldo de várias contas em uma única query (usado em listagens). */
    public static function saldosPorConta(array $ids): array
    {
        return Lancamento::query()
            ->whereIn('conta_id', $ids)
            ->groupBy('conta_id')
            ->pluck(DB::raw("COALESCE(SUM(CASE WHEN tipo = 'credito' THEN valor_centavos ELSE -valor_centavos END), 0)"), 'conta_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    public function podeDebitar(): bool
    {
        return ! $this->bloqueada
            && $this->status === self::STATUS_ATIVA
            && $this->status_abertura === self::ABERTURA_APROVADA;
    }

    public function estaEncerrada(): bool
    {
        return $this->status === self::STATUS_ENCERRADA;
    }
}
