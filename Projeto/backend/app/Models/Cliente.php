<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cliente extends Model
{
    protected $fillable = ['user_id', 'nome', 'cpf', 'data_nascimento'];

    protected function casts(): array
    {
        return ['data_nascimento' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Mostra só os dois últimos dígitos, como a documentação pede (RNF08)
    public function cpfMascarado(): string
    {
        return '***.***.***-'.substr($this->cpf, 9, 2);
    }
}
