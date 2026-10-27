<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'itens';

    protected $fillable = ['nome', 'valor_unitario_centavos'];

    protected function casts(): array
    {
        return ['valor_unitario_centavos' => 'integer'];
    }
}
