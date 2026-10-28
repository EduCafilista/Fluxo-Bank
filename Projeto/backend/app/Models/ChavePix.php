<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChavePix extends Model
{
    public const TIPO_CPF = 'CPF';

    public const TIPO_CNPJ = 'CNPJ';

    public const TIPO_EMAIL = 'EMAIL';

    public const TIPO_TELEFONE = 'TELEFONE';

    public const TIPO_ALEATORIA = 'ALEATORIA';

    public const TIPOS = [self::TIPO_CPF, self::TIPO_CNPJ, self::TIPO_EMAIL, self::TIPO_TELEFONE, self::TIPO_ALEATORIA];

    public const STATUS_ATIVA = 'ATIVA';

    public const STATUS_REVOGADA = 'REVOGADA';

    protected $table = 'chaves_pix';

    protected $fillable = ['conta_id', 'tipo_chave', 'valor_chave', 'status', 'registrado_em', 'desativada_em'];

    protected function casts(): array
    {
        return ['registrado_em' => 'datetime', 'desativada_em' => 'datetime'];
    }

    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class);
    }
}
