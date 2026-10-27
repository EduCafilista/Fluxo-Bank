<?php

namespace App\Exceptions;

class ContaBloqueadaException extends RegraDeNegocioException
{
    public function __construct(string $mensagem = 'Conta bloqueada: não é possível debitar.')
    {
        parent::__construct($mensagem, 403);
    }
}
