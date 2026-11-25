<?php

namespace App\Services;

use App\Exceptions\RegraDeNegocioException;
use App\Exceptions\SaldoInsuficienteException;
use App\Models\ChavePix;
use App\Models\CobrancaPix;
use App\Models\Conta;
use App\Models\Lancamento;
use App\Models\Transacao;
use App\Support\Emv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PagamentosService
{
    public function __construct(
        private LancamentoService $lancamentos,
        private TransferenciaService $transferencias,
    ) {}

    public function pagarBoleto(Conta $conta, string $linhaDigitavel, int $valorCentavos, ?string $chaveIdempotencia = null): Transacao
    {
        $digitos = $this->validarCodigoBoleto($linhaDigitavel);

        if ($valorCentavos <= 0) {
            throw new RegraDeNegocioException('O valor do boleto deve ser maior que zero.');
        }

        if (! $conta->podeDebitar()) {
            throw new RegraDeNegocioException('Conta não pode debitar (bloqueada ou abertura não aprovada).', 403);
        }

        return DB::transaction(function () use ($conta, $digitos, $valorCentavos, $chaveIdempotencia) {
            $travada = Conta::whereKey($conta->id)->lockForUpdate()->firstOrFail();

            if (! $travada->podeDebitar()) {
                throw new RegraDeNegocioException('Conta indisponível para pagamentos.', 403);
            }

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

    public function validarCodigoBoleto(string $codigo): string
    {
        $digitos = preg_replace('/[.\s-]/', '', $codigo);

        if (! ctype_digit($digitos) || ! in_array(strlen($digitos), [44, 47, 48], true)) {
            throw new RegraDeNegocioException('Informe um código de barras de 44 dígitos ou uma linha de 47 ou 48 dígitos.');
        }

        return $digitos;
    }

    public function gerarCobrancaPix(Conta $conta, int $valorCentavos, ?string $descricao = null): array
    {
        if ($valorCentavos <= 0) {
            throw new RegraDeNegocioException('O valor da cobrança deve ser maior que zero.');
        }

        if ($conta->estaEncerrada()) {
            throw new RegraDeNegocioException('Conta encerrada não pode emitir cobranças.');
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
        $cobranca = CobrancaPix::create([
            'conta_id' => $conta->id,
            'txid' => $txid,
            'hash_payload' => hash('sha256', $payload),
            'valor_centavos' => $valorCentavos,
            'expira_em' => now()->addMinutes(30),
        ]);

        return [
            'txid' => $txid,
            'chave' => $valor,
            'valor_centavos' => $valorCentavos,
            'descricao' => $descricao,
            'payload_emv' => $payload,
            'crc16' => substr($payload, -4),
            'valido' => Emv::checksumValido($payload),
            'expira_em' => $cobranca->expira_em->toIso8601String(),
            'expiracao_sugerida_minutos' => 30,
        ];
    }

    public function revisarCobrancaPix(Conta $origem, string $payload): array
    {
        $cobranca = $this->localizarCobranca($payload)->with('conta.cliente')->first();
        $this->validarCobranca($origem, $cobranca);

        if ($cobranca->transacao_id) {
            throw new RegraDeNegocioException('Esta cobrança Pix já foi paga.', 409);
        }

        return [
            'txid' => $cobranca->txid,
            'valor_centavos' => $cobranca->valor_centavos,
            'favorecido' => $cobranca->conta->cliente?->nomeExibicao() ?? $cobranca->conta->user?->name,
            'conta_numero' => $cobranca->conta->numero,
            'expira_em' => $cobranca->expira_em->toIso8601String(),
        ];
    }

    public function pagarCobrancaPix(Conta $origem, string $payload): Transacao
    {
        return DB::transaction(function () use ($origem, $payload) {
            $cobranca = $this->localizarCobranca($payload)->lockForUpdate()->first();

            if ($cobranca?->transacao_id) {
                $transacao = $cobranca->transacao;
                if ($transacao->lancamentos()->where('conta_id', $origem->id)->where('tipo', Lancamento::TIPO_DEBITO)->exists()) {
                    return $transacao;
                }

                throw new RegraDeNegocioException('Esta cobrança Pix já foi paga.', 409);
            }

            $this->validarCobranca($origem, $cobranca);
            $transacao = $this->transferencias->transferir(
                $origem, $cobranca->conta, $cobranca->valor_centavos, 'Cobrança Pix '.$cobranca->txid,
                Transacao::TIPO_PIX, 'pix-cobranca-'.$cobranca->id,
            );
            $cobranca->update(['transacao_id' => $transacao->id]);

            return $transacao;
        });
    }

    private function localizarCobranca(string $payload): Builder
    {
        $payload = trim($payload);
        if (! Emv::checksumValido($payload)) {
            throw new RegraDeNegocioException('Código Pix inválido. Confira o QR Code ou copie o código completo.');
        }

        return CobrancaPix::where('hash_payload', hash('sha256', $payload));
    }

    private function validarCobranca(Conta $origem, ?CobrancaPix $cobranca): void
    {
        if (! $cobranca) {
            throw new RegraDeNegocioException('Use uma cobrança Pix emitida pelo Fluxo.');
        }

        if ($cobranca->expira_em->isPast()) {
            throw new RegraDeNegocioException('Esta cobrança Pix expirou. Solicite um novo código.');
        }

        if ($cobranca->conta_id === $origem->id) {
            throw new RegraDeNegocioException('Use outra conta para pagar esta cobrança.');
        }

        if ($cobranca->conta->estaEncerrada()) {
            throw new RegraDeNegocioException('A conta recebedora está encerrada.');
        }
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
