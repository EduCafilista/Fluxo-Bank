<?php

namespace App\Services;

use App\Exceptions\RegraDeNegocioException;
use App\Exceptions\SaldoInsuficienteException;
use App\Models\ChavePix;
use App\Models\Conta;
use App\Models\Transacao;
use App\Support\Emv;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PagamentosService
{
    public function __construct(
        private LancamentoService $lancamentos,
        private MotorAntifraude $antifraude,
        private LimitePixService $limites,
    ) {}

    public function pagarBoleto(Conta $conta, string $linhaDigitavel, int $valorCentavos, ?string $chaveIdempotencia = null): Transacao
    {
        $digitos = preg_replace('/\D/', '', $linhaDigitavel);

        if (! in_array(strlen($digitos), [47, 48], true)) {
            throw new RegraDeNegocioException('Linha digitável inválida: são esperados 47 ou 48 dígitos.');
        }

        if ($valorCentavos <= 0) {
            throw new RegraDeNegocioException('O valor do boleto deve ser maior que zero.');
        }

        if (! $conta->podeDebitar()) {
            throw new RegraDeNegocioException('Conta não pode debitar (bloqueada ou abertura não aprovada).', 403);
        }

        return DB::transaction(function () use ($conta, $digitos, $valorCentavos, $chaveIdempotencia) {
            $travada = Conta::whereKey($conta->id)->lockForUpdate()->firstOrFail();

            if ($travada->saldo() < $valorCentavos) {
                throw new SaldoInsuficienteException;
            }

            $transacao = $this->lancamentos->debitar(
                $travada,
                $valorCentavos,
                Transacao::TIPO_BOLETO,
                'Pagamento de boleto '.substr($digitos, 0, 8).'...',
                $chaveIdempotencia,
            );

            app(AuditoriaService::class)->registrar(
                'MOVIMENTACAO',
                'BOLETO_PAGO',
                ['transacao_id' => $transacao->id, 'valor_centavos' => $valorCentavos],
                'transacoes',
                (string) $transacao->id,
            );

            return $transacao->load('lancamentos');
        });
    }

    public function gerarCobrancaPix(Conta $conta, int $valorCentavos, ?string $descricao = null): array
    {
        if ($valorCentavos <= 0) {
            throw new RegraDeNegocioException('O valor da cobrança deve ser maior que zero.');
        }

        $chave = $conta->chavesPix()->where('status', ChavePix::STATUS_ATIVA)->first();

        if (! $chave && $conta->cliente?->ehPessoaFisica() && $conta->cliente?->cpf) {
            $valor = $conta->cliente->cpf;
        } elseif ($chave) {
            $valor = $chave->valor_chave;
        } else {
            throw new RegraDeNegocioException('Cadastre uma chave Pix para gerar cobranças.');
        }

        $txid = Str::upper(Str::random(12));
        $payload = Emv::pixPayload($valor, $valorCentavos, $txid);

        return [
            'txid' => $txid,
            'chave' => $valor,
            'valor_centavos' => $valorCentavos,
            'descricao' => $descricao,
            'payload_emv' => $payload,
            'crc16' => substr($payload, -4),
            'valido' => substr($payload, -4) === Emv::crc16(substr($payload, 0, -4)),
            'expiracao_sugerida_minutos' => 30,
        ];
    }

    public function receberPix(Conta $conta, int $valorCentavos, ?string $descricao = null, ?string $chaveIdempotencia = null): Transacao
    {
        if ($valorCentavos <= 0) {
            throw new RegraDeNegocioException('O valor recebido deve ser maior que zero.');
        }

        if ($conta->estaEncerrada()) {
            throw new RegraDeNegocioException('Conta encerrada não pode receber.');
        }

        $transacao = $this->lancamentos->creditar(
            $conta,
            $valorCentavos,
            Transacao::TIPO_QR_CODE,
            $descricao ?? 'Recebimento Pix',
            $chaveIdempotencia,
        );

        app(AuditoriaService::class)->registrar(
            'MOVIMENTACAO',
            'PIX_RECEBIDO',
            ['transacao_id' => $transacao->id, 'valor_centavos' => $valorCentavos],
            'transacoes',
            (string) $transacao->id,
        );

        return $transacao->load('lancamentos');
    }

    public function depositar(Conta $conta, int $valorCentavos, ?string $descricao = null, ?string $chaveIdempotencia = null): Transacao
    {
        if ($valorCentavos <= 0 || $conta->estaEncerrada()) {
            throw new RegraDeNegocioException('Depósito inválido ou conta encerrada.');
        }

        return $this->lancamentos->creditar(
            $conta,
            $valorCentavos,
            Transacao::TIPO_DEPOSITO,
            $descricao ?? 'Depósito',
            $chaveIdempotencia,
        );
    }
}
