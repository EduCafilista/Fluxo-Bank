<?php

namespace App\Services;

use App\Exceptions\RegraDeNegocioException;
use App\Models\Conta;
use App\Models\Lancamento;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ContaService
{
    public function saldo(Conta $conta): int
    {
        return $conta->saldo();
    }

    public function extrato(Conta $conta, int $porPagina = 50): LengthAwarePaginator
    {
        $porPagina = min(max($porPagina, 1), 50);

        return $conta->lancamentos()
            ->with('transacao:id,tipo,status,descricao,created_at')
            ->latest('id')
            ->paginate($porPagina);
    }

    public function bloquear(Conta $conta, string $motivo): Conta
    {
        if ($conta->estaEncerrada()) {
            throw new RegraDeNegocioException('Conta encerrada não pode ser bloqueada.');
        }

        $conta->update(['bloqueada' => true, 'status' => Conta::STATUS_BLOQUEADA]);

        app(AuditoriaService::class)->registrar(
            'MOVIMENTACAO',
            'CONTA_BLOQUEADA_CLIENTE',
            ['motivo' => $motivo],
            'contas',
            (string) $conta->id,
        );

        return $conta->refresh();
    }

    public function desbloquear(Conta $conta): Conta
    {
        if ($conta->status_abertura !== Conta::ABERTURA_APROVADA) {
            throw new RegraDeNegocioException('Conta com abertura não aprovada não pode ser desbloqueada.');
        }

        $conta->update(['bloqueada' => false, 'status' => Conta::STATUS_ATIVA]);

        return $conta->refresh();
    }

    public function encerrar(Conta $conta): Conta
    {
        if ($conta->saldo() !== 0) {
            throw new RegraDeNegocioException('Só é possível encerrar a conta com saldo zerado.');
        }

        if ($conta->agendamentos()->where('status', 'AGENDADO')->exists()) {
            throw new RegraDeNegocioException('Cancele os agendamentos pendentes antes de encerrar a conta.');
        }

        $conta->update(['status' => Conta::STATUS_ENCERRADA, 'bloqueada' => true]);

        app(AuditoriaService::class)->registrar(
            'MOVIMENTACAO',
            'CONTA_ENCERRADA',
            [],
            'contas',
            (string) $conta->id,
        );

        return $conta->refresh();
    }

    public function aprovarAbertura(Conta $conta): Conta
    {
        $conta->update([
            'status_abertura' => Conta::ABERTURA_APROVADA,
            'status_abertura_atualizado_em' => now(),
            'status' => Conta::STATUS_ATIVA,
            'bloqueada' => false,
        ]);

        $conta->cliente?->update(['status' => 'ATIVO']);

        app(AuditoriaService::class)->registrar(
            'MOVIMENTACAO',
            'KYC_APROVADO',
            [],
            'contas',
            (string) $conta->id,
        );

        return $conta->refresh();
    }

    public function recusarAbertura(Conta $conta, string $motivo): Conta
    {
        $conta->update([
            'status_abertura' => Conta::ABERTURA_RECUSADA,
            'status_abertura_atualizado_em' => now(),
            'status' => Conta::STATUS_BLOQUEADA,
            'bloqueada' => true,
        ]);

        $conta->cliente?->update(['status' => 'BLOQUEADO']);

        app(AuditoriaService::class)->registrar(
            'MOVIMENTACAO',
            'KYC_RECUSADO',
            ['motivo' => $motivo],
            'contas',
            (string) $conta->id,
        );

        return $conta->refresh();
    }

    public function bloquearPorFraude(Conta $conta, string $motivo): Conta
    {
        $conta->update(['bloqueada' => true, 'status' => Conta::STATUS_BLOQUEADA]);
        $conta->cliente?->update(['status' => 'BLOQUEADO']);

        app(AuditoriaService::class)->registrar(
            'MOVIMENTACAO',
            'CONTA_BLOQUEADA_FRAUDE',
            ['motivo' => $motivo],
            'contas',
            (string) $conta->id,
        );

        return $conta->refresh();
    }

    public function resumoDoDia(Conta $conta): array
    {
        $lancamentos = Lancamento::where('conta_id', $conta->id)
            ->whereDate('created_at', now()->toDateString())
            ->get();

        return [
            'creditos_centavos' => (int) $lancamentos->where('tipo', 'credito')->sum('valor_centavos'),
            'debitos_centavos' => (int) $lancamentos->where('tipo', 'debito')->sum('valor_centavos'),
            'quantidade' => $lancamentos->count(),
        ];
    }
}
