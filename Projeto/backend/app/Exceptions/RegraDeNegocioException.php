<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Base das exceções de domínio do Fluxo.
 * O handler em bootstrap/app.php converte qualquer uma delas em JSON 422
 * (ou no status definido em $status) com a chave "erro", no padrão da API.
 */
class RegraDeNegocioException extends RuntimeException
{
    public function __construct(string $mensagem, public readonly int $status = 422)
    {
        parent::__construct($mensagem);
    }

    public function statusHttp(): int
    {
        return $this->status;
    }
}
