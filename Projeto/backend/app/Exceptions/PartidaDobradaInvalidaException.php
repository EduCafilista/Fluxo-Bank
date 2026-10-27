<?php

namespace App\Exceptions;

/**
 * RN02/RN03: a soma dos lançamentos de uma transação tem de ser zero.
 * Nenhuma transação pode ser gravada com um único lado.
 */
class PartidaDobradaInvalidaException extends RegraDeNegocioException
{
    public function __construct(string $mensagem = 'Transação inválida: as partidas dobradas não fecham em zero.')
    {
        parent::__construct($mensagem, 500);
    }
}
