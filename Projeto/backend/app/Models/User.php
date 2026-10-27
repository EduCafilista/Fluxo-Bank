<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /** Perfis do sistema (RF09 - RBAC) */
    public const PERFIL_CLIENTE = 'CLIENTE';

    public const PERFIL_ATENDENTE = 'ATENDENTE';

    public const PERFIL_ADMIN = 'ADMIN';

    protected $fillable = [
        'name', 'email', 'password',
        'perfil', 'dois_fatores_ativo', 'tentativas_login', 'bloqueado_ate',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'dois_fatores_ativo' => 'boolean',
            'bloqueado_ate' => 'datetime',
        ];
    }

    // Dados cadastrais (PF ou PJ)
    public function cliente(): HasOne
    {
        return $this->hasOne(Cliente::class);
    }

    // Um cliente pode ter mais de uma conta (Conta Corrente, Poupança, ...),
    // por isso a relação "principal" (conta) e a coleção completa (contas).
    public function contas(): HasMany
    {
        return $this->hasMany(Conta::class);
    }

    public function conta(): HasOne
    {
        return $this->hasOne(Conta::class)->orderBy('id');
    }

    public function codigosVerificacao(): HasMany
    {
        return $this->hasMany(CodigoVerificacao::class);
    }

    public function chamados(): HasMany
    {
        return $this->hasMany(ChamadoSuporte::class);
    }

    public function ehBackoffice(): bool
    {
        return in_array($this->perfil, [self::PERFIL_ATENDENTE, self::PERFIL_ADMIN], true);
    }

    public function bloqueadoParaLogin(): bool
    {
        return $this->bloqueado_ate !== null && $this->bloqueado_ate->isFuture();
    }
}
