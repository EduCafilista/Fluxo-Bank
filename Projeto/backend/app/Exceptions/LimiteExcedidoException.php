<?php

namespace App\Exceptions;

class LimiteExcedidoException extends RegraDeNegocioException
{
    public function __construct(string $mensagem = 'Limite excedido.')
    {
        parent::__construct($mensagem);
    }
}
