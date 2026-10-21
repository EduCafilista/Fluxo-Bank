<?php

namespace App\Services;

use App\Models\Conta;
use App\Models\Lancamento;
use App\Models\Transacao;
use DomainException;
use Illuminate\Support\Facades\DB;

class TransferenciaService
{
    // Toda regra de transferência fica aqui, não no controller
    public function transferir(Conta $origem, Conta $destino, int $valorCentavos, ?string $descricao = null): Transacao
    {
        if ($valorCentavos <= 0) {
            throw new DomainException('O valor deve ser maior que zero.');
        }

        if ($origem->id === $destino->id) {
            throw new DomainException('Não é possível transferir para a mesma conta.');
        }

        // Tudo ou nada: se algo falhar, nenhum lançamento é gravado
        return DB::transaction(function () use ($origem, $destino, $valorCentavos, $descricao) {
            // Trava a conta de origem para evitar dois saques simultâneos
            $origem = Conta::whereKey($origem->id)->lockForUpdate()->firstOrFail();

            if ($origem->saldo() < $valorCentavos) {
                throw new DomainException('Saldo insuficiente.');
            }

            $transacao = Transacao::create(['descricao' => $descricao]);

            Lancamento::create([
                'transacao_id' => $transacao->id,
                'conta_id' => $origem->id,
                'tipo' => 'debito',
                'valor_centavos' => $valorCentavos,
            ]);

            Lancamento::create([
                'transacao_id' => $transacao->id,
                'conta_id' => $destino->id,
                'tipo' => 'credito',
                'valor_centavos' => $valorCentavos,
            ]);

            return $transacao;
        });
    }
}
