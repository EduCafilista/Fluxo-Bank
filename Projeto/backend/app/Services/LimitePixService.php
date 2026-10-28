<?php

namespace App\Services;

use App\Exceptions\LimiteExcedidoException;
use App\Models\ConsumoLimitePix;
use App\Models\Conta;
use Illuminate\Support\Carbon;

class LimitePixService
{
    public function politica(Conta $conta): array
    {
        $limite = $conta->limitesPix()
            ->where('ativo', true)
            ->where(fn ($q) => $q->whereNull('vigente_ate')->orWhere('vigente_ate', '>', now()))
            ->latest('id')
            ->first();

        $padrao = config('fluxo.limites_pix');

        return [
            'limite_pix_id' => $limite?->id,
            'por_transacao' => $limite?->limite_por_transacao ?? $padrao['por_transacao'],
            'diario' => $limite?->limite_diario ?? $padrao['diario'],
            'quantidade_diaria' => $limite?->quantidade_diaria ?? $padrao['quantidade_diaria'],
            'noturno_por_transacao' => $limite?->limite_noturno_por_transacao ?? $padrao['noturno_por_transacao'],
            'noturno_diario' => $limite?->limite_noturno_diario ?? $padrao['noturno_diario'],
            'quantidade_noturna' => $limite?->quantidade_noturna ?? $padrao['quantidade_noturna'],
            'sem_chave_por_transacao' => $limite?->limite_sem_chave_por_transacao ?? $padrao['sem_chave_por_transacao'],
            'sem_chave_diario' => $limite?->limite_sem_chave_diario ?? $padrao['sem_chave_diario'],
            'hora_inicio_noturno' => substr((string) ($limite?->hora_inicio_noturno ?? $padrao['hora_inicio_noturno']), 0, 5),
            'hora_fim_noturno' => substr((string) ($limite?->hora_fim_noturno ?? $padrao['hora_fim_noturno']), 0, 5),
        ];
    }

    public function janela(array $politica, ?Carbon $momento = null): string
    {
        $momento = $momento ?? now();
        $hora = $momento->format('H:i');
        $inicio = $politica['hora_inicio_noturno'];
        $fim = $politica['hora_fim_noturno'];

        $noturna = $inicio > $fim
            ? ($hora >= $inicio || $hora < $fim)
            : ($hora >= $inicio && $hora < $fim);

        return $noturna ? ConsumoLimitePix::JANELA_NOTURNA : ConsumoLimitePix::JANELA_DIURNA;
    }

    /**
     * @return array{politica:array, janela:string, consumo:ConsumoLimitePix}
     */
    public function verificar(Conta $conta, int $valorCentavos): array
    {
        if ($valorCentavos <= 0) {
            throw new LimiteExcedidoException('O valor deve ser maior que zero.');
        }

        $politica = $this->politica($conta);
        $janela = $this->janela($politica);
        $temChaveAtiva = $conta->chavesPix()->where('status', 'ATIVA')->exists();

        $tetoTransacao = $janela === ConsumoLimitePix::JANELA_NOTURNA
            ? $politica['noturno_por_transacao']
            : $politica['por_transacao'];

        if (! $temChaveAtiva) {
            $tetoTransacao = min($tetoTransacao, $politica['sem_chave_por_transacao']);
        }

        if ($valorCentavos > $tetoTransacao) {
            throw new LimiteExcedidoException(sprintf(
                'Valor acima do limite por transação (%s).',
                $this->fmt($tetoTransacao),
            ));
        }

        $consumo = $this->consumoDoDia($conta, $janela);

        $tetoDiario = $janela === ConsumoLimitePix::JANELA_NOTURNA
            ? $politica['noturno_diario']
            : $politica['diario'];

        if (! $temChaveAtiva) {
            $tetoDiario = min($tetoDiario, $politica['sem_chave_diario']);
        }

        $jaUsado = $consumo->valor_utilizado + $consumo->valor_reservado;

        if ($jaUsado + $valorCentavos > $tetoDiario) {
            throw new LimiteExcedidoException(sprintf(
                'Limite diário excedido (%s por dia). Já utilizado: %s.',
                $this->fmt($tetoDiario),
                $this->fmt($jaUsado),
            ));
        }

        $qtdMax = $janela === ConsumoLimitePix::JANELA_NOTURNA
            ? $politica['quantidade_noturna']
            : $politica['quantidade_diaria'];

        if ($consumo->quantidade_utilizada + $consumo->quantidade_reservada + 1 > $qtdMax) {
            throw new LimiteExcedidoException("Quantidade de transferências do dia excedida (máx. {$qtdMax}).");
        }

        return ['politica' => $politica, 'janela' => $janela, 'consumo' => $consumo];
    }

    public function reservar(Conta $conta, int $valorCentavos): ConsumoLimitePix
    {
        $verificacao = $this->verificar($conta, $valorCentavos);
        $consumo = $verificacao['consumo'];

        $consumo->increment('valor_reservado', $valorCentavos);
        $consumo->increment('quantidade_reservada');

        return $consumo->refresh();
    }

    public function consumirReservado(ConsumoLimitePix $consumo, int $valorCentavos): ConsumoLimitePix
    {
        $consumo->increment('valor_reservado', $valorCentavos);
        $consumo->increment('quantidade_reservada');

        return $consumo->refresh();
    }

    public function consumir(ConsumoLimitePix $consumo, int $valorCentavos): void
    {
        $consumo->decrement('valor_reservado', $valorCentavos);
        $consumo->decrement('quantidade_reservada');
        $consumo->increment('valor_utilizado', $valorCentavos);
        $consumo->increment('quantidade_utilizada');
    }

    public function devolverReserva(ConsumoLimitePix $consumo, int $valorCentavos): void
    {
        $consumo->decrement('valor_reservado', $valorCentavos);
        $consumo->decrement('quantidade_reservada');
        $consumo->refresh();
    }

    public function confirmar(ConsumoLimitePix $consumo, int $valorCentavos): void
    {
        $this->consumir($consumo, $valorCentavos);
    }

    public function consumoDoDia(Conta $conta, string $janela): ConsumoLimitePix
    {
        return ConsumoLimitePix::firstOrCreate(
            [
                'conta_id' => $conta->id,
                'data_referencia' => now()->toDateString(),
                'janela' => $janela,
            ],
            ['limite_pix_id' => $this->politica($conta)['limite_pix_id']],
        );
    }

    public function resumo(Conta $conta): array
    {
        $politica = $this->politica($conta);
        $janela = $this->janela($politica);
        $consumo = $this->consumoDoDia($conta, $janela);
        $temChave = $conta->chavesPix()->where('status', 'ATIVA')->exists();

        $tetoDiario = $janela === ConsumoLimitePix::JANELA_NOTURNA
            ? $politica['noturno_diario']
            : $politica['diario'];

        if (! $temChave) {
            $tetoDiario = min($tetoDiario, $politica['sem_chave_diario']);
        }

        return [
            'janela' => $janela,
            'tem_chave_pix_ativa' => $temChave,
            'limite_por_transacao_centavos' => $janela === ConsumoLimitePix::JANELA_NOTURNA
                ? $politica['noturno_por_transacao']
                : $politica['por_transacao'],
            'limite_diario_centavos' => $tetoDiario,
            'utilizado_centavos' => $consumo->valor_utilizado,
            'reservado_centavos' => $consumo->valor_reservado,
            'disponivel_centavos' => max(0, $tetoDiario - $consumo->valor_utilizado - $consumo->valor_reservado),
        ];
    }

    private function fmt(int $centavos): string
    {
        return 'R$ '.number_format($centavos / 100, 2, ',', '.');
    }
}
