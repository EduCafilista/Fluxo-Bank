<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    // Dados cadastrais (nome, CPF, nascimento) de Pessoa Física
    public function cliente(): HasOne
    {
        return $this->hasOne(Cliente::class);
    }

    // Por simplicidade, cada usuário tem uma conta
    public function conta(): HasOne
    {
        return $this->hasOne(Conta::class);
    }
}
