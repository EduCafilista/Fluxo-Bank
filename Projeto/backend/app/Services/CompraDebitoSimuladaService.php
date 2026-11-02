<?php

namespace App\Services;

use App\Exceptions\RegraDeNegocioException;
use App\Exceptions\SaldoInsuficienteException;
use App\Models\Cartao;
use App\Models\Conta;
use App\Models\Transacao;
use Illuminate\Support\Facades\DB;

class CompraDebitoSimuladaService
{
    public function __construct(private LancamentoService $lancamentos) {}

    public function comprar(Cartao $cartao, int $valorCentavos, string $estabelecimento, string $chave, bool $online = false): array
    {
        if ($valorCentavos <= 0) {
            throw new RegraDeNegocioException('O valor deve ser positivo.');
        }

        $idempotencia = 'debito:'.hash('sha256', $cartao->id.'|'.$chave);
        $fingerprint = hash('sha256', json_encode([$cartao->id, $valorCentavos, $estabelecimento, $online], JSON_UNESCAPED_UNICODE));

        return DB::transaction(function () use ($cartao, $valorCentavos, $estabelecimento, $online, $idempotencia, $fingerprint) {
            // A conta é travada primeiro; outras compras da mesma conta são serializadas.
            $conta = Conta::whereKey($cartao->conta_id)->lockForUpdate()->firstOrFail();
            $registro = DB::table('compras_debito_simuladas')->where('chave_idempotencia', $idempotencia)->first();
            if ($registro) {
                if (! hash_equals($registro->fingerprint, $fingerprint)) {
                    throw new RegraDeNegocioException('Chave de idempotência reutilizada com dados diferentes.', 409);
                }
                return ['transacao' => Transacao::findOrFail($registro->transacao_id)->load('lancamentos'), 'repetida' => true];
            }

            $cartaoAtual = Cartao::whereKey($cartao->id)->lockForUpdate()->firstOrFail();
            if ($cartaoAtual->modalidade !== Cartao::MODALIDADE_DEBITO || ! $cartaoAtual->ativo()) {
                throw new RegraDeNegocioException('Cartão de débito inexistente ou inativo.');
            }
            if ($cartaoAtual->validade && $cartaoAtual->validade->endOfMonth()->isPast()) {
                throw new RegraDeNegocioException('Cartão vencido.');
            }
            if (! $conta->podeDebitar()) {
                throw new RegraDeNegocioException('Conta não está habilitada para débito.', 403);
            }
            if ($online && $cartaoAtual->fisico && ! $cartaoAtual->fisico->permite_compras_online) {
                throw new RegraDeNegocioException('Compras online desabilitadas para este cartão.', 403);
            }
            if ($online && ! $cartaoAtual->fisico && ! $cartaoAtual->virtual) {
                throw new RegraDeNegocioException('Cartão sem modalidade física ou virtual válida.', 403);
            }
            if ($conta->saldo() < $valorCentavos) {
                throw new SaldoInsuficienteException;
            }

            $transacao = $this->lancamentos->debitar(
                $conta, $valorCentavos, 'COMPRA_DEBITO',
                'Compra débito simulada - '.$estabelecimento, $idempotencia
            );
            DB::table('compras_debito_simuladas')->insert([
                'cartao_id' => $cartaoAtual->id,
                'transacao_id' => $transacao->id,
                'valor_centavos' => $valorCentavos,
                'estabelecimento' => $estabelecimento,
                'online' => $online,
                'chave_idempotencia' => $idempotencia,
                'fingerprint' => $fingerprint,
                'created_at' => now(),
            ]);
            app(AuditoriaService::class)->registrar('MOVIMENTACAO', 'COMPRA_DEBITO_SIMULADA', [
                'cartao_id' => $cartaoAtual->id, 'transacao_id' => $transacao->id,
                'valor_centavos' => $valorCentavos, 'estabelecimento' => $estabelecimento,
            ], 'transacoes', (string) $transacao->id);

            return ['transacao' => $transacao->load('lancamentos'), 'repetida' => false];
        });
    }
}
