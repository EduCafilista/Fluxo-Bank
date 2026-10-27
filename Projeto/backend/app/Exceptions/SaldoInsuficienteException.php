<?php

namespace App\Exceptions;

class SaldoInsuficienteException extends RegraDeNegocioException
{
    public function __construct(string $mensagem = 'Saldo insuficiente.')
    {
        parent::__construct($mensagem);
    }
}
