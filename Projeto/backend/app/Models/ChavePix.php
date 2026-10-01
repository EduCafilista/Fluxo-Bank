<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChavePix extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'chaves_pix';

    protected $fillable = [
        'conta_id',
        'tipo_chave',
        'valor_chave',
        'registrado_em',
    ];

    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class, 'conta_id');
    }
}
