<?php

namespace App\Services;

use App\Exceptions\RegraDeNegocioException;
use App\Models\Agendamento;
use App\Models\Conta;
use App\Models\Transacao;
use App\Notifications\AgendamentoExecutado;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AgendamentoService
{
    public function __construct(
        private TransferenciaService $transferencias,
        private PagamentosService $pagamentos,
    ) {}

    public function agendar(
        Conta $origem,
        Conta $destino,
        int $valorCentavos,
        Carbon|string $quando,
        ?string $descricao = null,
        string $tipo = Agendamento::TIPO_TED_INTERNA,
        string $chaveIdempotencia = '',
        bool $recorrente = false,
    ): Agendamento {
        if ($origem->is($destino) || $destino->estaEncerrada()) {
            throw new RegraDeNegocioException('Selecione outra conta de destino disponível.');
        }

        if (! in_array($tipo, [Agendamento::TIPO_TED_INTERNA, Agendamento::TIPO_PIX], true)) {
            throw new RegraDeNegocioException('Tipo de transferência agendada inválido.');
        }

        return $this->criar(
            $origem, $valorCentavos, $quando, $tipo,
            ['conta_destino_id' => $destino->id, 'descricao' => $descricao], $chaveIdempotencia, $recorrente,
        );
    }

    public function agendarBoleto(
        Conta $origem,
        string $codigo,
        int $valorCentavos,
        Carbon|string $quando,
        string $chaveIdempotencia = '',
    ): Agendamento {
        return $this->criar(
            $origem, $valorCentavos, $quando, Agendamento::TIPO_BOLETO,
            ['linha_digitavel' => $this->pagamentos->validarCodigoBoleto($codigo)], $chaveIdempotencia, false,
        );
    }

    private function criar(
        Conta $origem,
        int $valorCentavos,
        Carbon|string $quando,
        string $tipo,
        array $payload,
        string $chaveIdempotencia,
        bool $recorrente,
    ): Agendamento {
        $quando = $quando instanceof Carbon ? $quando->copy() : Carbon::parse($quando);
        $quando->setTimezone(config('app.timezone'))->microsecond(0);

        if ($valorCentavos <= 0) {
            throw new RegraDeNegocioException('O valor deve ser maior que zero.');
        }

        $chave = $chaveIdempotencia !== ''
            ? 'AG-'.hash('sha256', $origem->id.'|'.$chaveIdempotencia)
            : 'AG-'.Str::uuid();

        if ($recorrente) {
            $payload['dia_recorrencia'] = $quando->day;
        }

        return DB::transaction(function () use ($origem, $valorCentavos, $quando, $tipo, $payload, $chave, $chaveIdempotencia, $recorrente) {
            $origem = Conta::query()->lockForUpdate()->findOrFail($origem->id);
            $existente = Agendamento::where('chave_idempotencia', $chave)
                ->orWhere(fn ($q) => $q->where('chave_idempotencia', $chaveIdempotencia)->where('conta_id', $origem->id))
                ->first();

            if ($existente) {
                if ($existente->valor_centavos !== $valorCentavos || $existente->tipo_operacao !== $tipo
                    || ! $existente->agendado_para->equalTo($quando) || $existente->payload_operacao != $payload
                    || $existente->recorrente !== $recorrente) {
                    throw new RegraDeNegocioException('Já existe um agendamento com esta chave e dados diferentes.', 409);
                }

                return $existente;
            }

            if ($quando->isPast()) {
                throw new RegraDeNegocioException('A data do agendamento precisa estar no futuro.');
            }

            if ($origem->estaEncerrada()) {
                throw new RegraDeNegocioException('Conta encerrada não permite agendamentos.');
            }

            $agendamento = Agendamento::create([
                'conta_id' => $origem->id,
                'tipo_operacao' => $tipo,
                'valor_centavos' => $valorCentavos,
                'agendado_para' => $quando,
                'payload_operacao' => $payload,
                'status' => Agendamento::STATUS_AGENDADO,
                'chave_idempotencia' => $chave,
                'recorrente' => $recorrente,
                'proxima_execucao' => $quando,
            ]);

            app(AuditoriaService::class)->registrar(
                'MOVIMENTACAO', 'AGENDAMENTO_CRIADO',
                ['agendamento_id' => $agendamento->id, 'valor_centavos' => $valorCentavos, 'quando' => $quando->toIso8601String()],
                'agendamentos', (string) $agendamento->id,
            );

            return $agendamento;
        });
    }

    public function cancelar(Agendamento $agendamento, Conta $conta): Agendamento
    {
        if ($agendamento->conta_id !== $conta->id) {
            throw new RegraDeNegocioException('Agendamento não pertence a esta conta.', 403);
        }

        return DB::transaction(function () use ($agendamento) {
            $travado = Agendamento::query()->lockForUpdate()->findOrFail($agendamento->id);
            if ($travado->status !== Agendamento::STATUS_AGENDADO) {
                throw new RegraDeNegocioException('Somente agendamentos pendentes podem ser cancelados.');
            }

            $travado->update(['status' => Agendamento::STATUS_CANCELADO, 'proxima_execucao' => null]);

            return $travado;
        });
    }

    public function executar(Agendamento $agendamento): string
    {
        return DB::transaction(function () use ($agendamento) {
            $travado = Agendamento::query()->lockForUpdate()->findOrFail($agendamento->id);

            if ($travado->status !== Agendamento::STATUS_AGENDADO || $travado->agendado_para->isFuture()
                || ($travado->tentativas > 0 && $travado->proxima_execucao?->isFuture())) {
                return $travado->status;
            }

            if ($travado->tentativas >= Agendamento::MAX_TENTATIVAS) {
                $travado->update(['status' => Agendamento::STATUS_FALHOU, 'erro' => 'Máximo de tentativas atingido.']);

                return $travado->status;
            }

            $origem = $travado->conta;

            try {
                // O savepoint impede que uma falha na execução confirme lançamentos isolados.
                DB::transaction(function () use ($travado, $origem) {
                    $transacao = $this->executarOperacao($travado, $origem);
                    $travado->update([
                        'status' => Agendamento::STATUS_EXECUTADO,
                        'transacao_id' => $transacao->id,
                        'tentativas' => $travado->tentativas + 1,
                        'proxima_execucao' => null,
                        'erro' => null,
                    ]);
                    $origem->user?->notify(new AgendamentoExecutado($travado));
                    $this->programarProximaOcorrencia($travado);
                });
            } catch (\Throwable $e) {
                $travado->refresh();
                $tentativas = $travado->tentativas + 1;
                $falhou = $tentativas >= Agendamento::MAX_TENTATIVAS;
                if (! $e instanceof RegraDeNegocioException) {
                    report($e);
                }

                $travado->update([
                    'tentativas' => $tentativas,
                    'status' => $falhou ? Agendamento::STATUS_FALHOU : Agendamento::STATUS_AGENDADO,
                    'proxima_execucao' => $falhou ? null : now()->addMinutes(15),
                    'erro' => $e instanceof RegraDeNegocioException ? $e->getMessage() : 'A execução encontrou um erro interno.',
                ]);
            }

            return $travado->status;
        });
    }

    private function executarOperacao(Agendamento $agendamento, Conta $origem): Transacao
    {
        $payload = $agendamento->payload_operacao;
        $chave = 'agendamento-'.$agendamento->id;
        if ($agendamento->tipo_operacao === Agendamento::TIPO_BOLETO) {
            return $this->pagamentos->pagarBoleto($origem, $payload['linha_digitavel'], $agendamento->valor_centavos, $chave);
        }

        $destino = Conta::find($payload['conta_destino_id'] ?? null);
        if (! $destino) {
            throw new RegraDeNegocioException('A conta de destino do agendamento não existe.');
        }

        return $this->transferencias->transferir(
            $origem, $destino, $agendamento->valor_centavos,
            $payload['descricao'] ?? 'Transferência agendada', $agendamento->tipo_operacao, $chave,
        );
    }

    private function programarProximaOcorrencia(Agendamento $agendamento): void
    {
        if (! $agendamento->recorrente) {
            return;
        }

        $dia = $agendamento->payload_operacao['dia_recorrencia'] ?? $agendamento->agendado_para->day;
        $proxima = $agendamento->agendado_para->copy()->addMonthNoOverflow();
        $proxima->day(min($dia, $proxima->daysInMonth));
        $novo = $agendamento->replicate();
        $novo->fill([
            'transacao_id' => null,
            'status' => Agendamento::STATUS_AGENDADO,
            'tentativas' => 0,
            'chave_idempotencia' => 'recorrencia-'.$agendamento->id,
            'agendado_para' => $proxima,
            'proxima_execucao' => $proxima,
        ])->save();
    }

    public function expirarAntigos(): int
    {
        return Agendamento::where('status', Agendamento::STATUS_AGENDADO)
            ->where('agendado_para', '<', now()->subDays(7))
            ->update(['status' => Agendamento::STATUS_EXPIRADO]);
    }
}
