<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

// Valida o CPF pelos dois dígitos verificadores (RF01).
// Aceita com ou sem pontos e traço.
class Cpf implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cpf = preg_replace('/\D/', '', (string) $value);

        // 11 dígitos e não pode ser uma sequência repetida (ex.: 111.111.111-11)
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            $fail('O CPF informado é inválido.');

            return;
        }

        // Confere o 1º e o 2º dígito verificador
        for ($posicao = 9; $posicao < 11; $posicao++) {
            $soma = 0;
            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $cpf[$i] * (($posicao + 1) - $i);
            }

            $digitoEsperado = ((10 * $soma) % 11) % 10;

            if ((int) $cpf[$posicao] !== $digitoEsperado) {
                $fail('O CPF informado é inválido.');

                return;
            }
        }
    }
}
