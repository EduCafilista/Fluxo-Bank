<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory;

    public const TIPO_PF = 'PF';

    public const TIPO_PJ = 'PJ';

    public const STATUS_PENDENTE = 'PENDENTE';

    public const STATUS_ATIVO = 'ATIVO';

    public const STATUS_BLOQUEADO = 'BLOQUEADO';

    public const STATUS_ENCERRADO = 'ENCERRADO';

    protected $fillable = [
        'user_id', 'tipo_cliente', 'nome', 'cpf', 'razao_social', 'cnpj',
        'data_nascimento', 'representante_legal', 'status',
    ];

    protected function casts(): array
    {
        return ['data_nascimento' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contas(): HasMany
    {
        return $this->hasMany(Conta::class);
    }

    public function assinaturas(): HasMany
    {
        return $this->hasMany(Assinatura::class);
    }

    public function ehPessoaFisica(): bool
    {
        return $this->tipo_cliente === self::TIPO_PF;
    }

    // Mostra só os dois últimos dígitos (RNF08 / LGPD)
    public function cpfMascarado(): ?string
    {
        if (! $this->cpf) {
            return null;
        }

        return '***.***.***-'.substr($this->cpf, 9, 2);
    }

    public function cnpjMascarado(): ?string
    {
        if (! $this->cnpj) {
            return null;
        }

        return '**.***.***/****-'.substr($this->cnpj, 12, 2);
    }

    public function documentoMascarado(): ?string
    {
        return $this->ehPessoaFisica() ? $this->cpfMascarado() : $this->cnpjMascarado();
    }

    public function nomeExibicao(): string
    {
        return $this->razao_social ?: $this->nome;
    }
}
