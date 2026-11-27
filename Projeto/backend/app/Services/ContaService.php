<?php

namespace App\Services;

use App\Exceptions\RegraDeNegocioException;
use App\Models\Agendamento;
use App\Models\ChavePix;
use App\Models\Cliente;
use App\Models\Conta;
use App\Models\Lancamento;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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
        return $this->alterar($conta, function (Conta $conta) use ($motivo) {
            if ($conta->status_abertura !== Conta::ABERTURA_APROVADA || $conta->bloqueio_backoffice) {
                throw new RegraDeNegocioException('O bloqueio preventivo exige uma conta aprovada e disponível.');
            }

            $conta->update(['bloqueada' => true, 'status' => Conta::STATUS_BLOQUEADA]);
            $this->registrar($conta, 'CONTA_BLOQUEADA_CLIENTE', ['motivo' => $motivo]);
        });
    }

    public function desbloquear(Conta $conta, bool $porAdministrador = false): Conta
    {
        return $this->alterar($conta, function (Conta $conta) use ($porAdministrador) {
            if ($conta->status_abertura !== Conta::ABERTURA_APROVADA) {
                throw new RegraDeNegocioException('Conta com abertura não aprovada não pode ser desbloqueada.');
            }

            if ($conta->bloqueio_backoffice && ! $porAdministrador) {
                throw new RegraDeNegocioException('O bloqueio por fraude deve ser analisado pela administração.', 403);
            }

            if ($conta->bloqueio_backoffice) {
                $conta->cliente?->update(['status' => Cliente::STATUS_ATIVO]);
            }

            $conta->update(['bloqueada' => false, 'bloqueio_backoffice' => false, 'status' => Conta::STATUS_ATIVA]);
            $this->registrar($conta, 'CONTA_DESBLOQUEADA', ['administrativo' => $porAdministrador]);
        });
    }

    public function encerrar(Conta $conta): Conta
    {
        return DB::transaction(function () use ($conta) {
            $conta = Conta::query()->lockForUpdate()->findOrFail($conta->id);

            if ($conta->estaEncerrada()) {
                return $conta;
            }

            if ($conta->tipo_conta === 'INSTITUCIONAL') {
                throw new RegraDeNegocioException('A conta institucional é protegida.');
            }

            if ($conta->saldo() !== 0) {
                throw new RegraDeNegocioException('Só é possível encerrar a conta com saldo zerado.');
            }

            if ($conta->agendamentos()->where('status', Agendamento::STATUS_AGENDADO)->exists()) {
                throw new RegraDeNegocioException('Cancele os agendamentos pendentes antes de encerrar a conta.');
            }

            $conta->update(['status' => Conta::STATUS_ENCERRADA, 'bloqueada' => true]);
            $conta->chavesPix()->where('status', ChavePix::STATUS_ATIVA)->update([
                'status' => ChavePix::STATUS_REVOGADA, 'desativada_em' => now(),
            ]);
            $this->registrar($conta, 'CONTA_ENCERRADA');

            return $conta;
        });
    }

    public function aprovarAbertura(Conta $conta): Conta
    {
        return $this->alterar($conta, function (Conta $conta) {
            $this->exigirKycPendente($conta);
            $conta->update([
                'status_abertura' => Conta::ABERTURA_APROVADA,
                'status_abertura_atualizado_em' => now(),
                'status' => $conta->bloqueio_backoffice ? Conta::STATUS_BLOQUEADA : Conta::STATUS_ATIVA,
                'bloqueada' => $conta->bloqueio_backoffice,
            ]);

            if (! $conta->bloqueio_backoffice) {
                $conta->cliente?->update(['status' => Cliente::STATUS_ATIVO]);
            }

            $this->registrar($conta, 'KYC_APROVADO');
            $id = $conta->id;
            DB::afterCommit(fn () => app(NotificacaoAberturaService::class)->enviarDecisao($id, true));
        });
    }

    public function recusarAbertura(Conta $conta, string $motivo): Conta
    {
        return $this->alterar($conta, function (Conta $conta) use ($motivo) {
            $this->exigirKycPendente($conta);
            $conta->update([
                'status_abertura' => Conta::ABERTURA_RECUSADA,
                'status_abertura_atualizado_em' => now(),
                'status' => Conta::STATUS_BLOQUEADA,
                'bloqueada' => true,
            ]);
            $conta->cliente?->update(['status' => Cliente::STATUS_BLOQUEADO]);
            $this->registrar($conta, 'KYC_RECUSADO', ['motivo' => $motivo]);
            $id = $conta->id;
            DB::afterCommit(fn () => app(NotificacaoAberturaService::class)->enviarDecisao($id, false, $motivo));
        });
    }

    public function bloquearPorFraude(Conta $conta, string $motivo): Conta
    {
        return $this->alterar($conta, function (Conta $conta) use ($motivo) {
            $conta->update(['bloqueada' => true, 'bloqueio_backoffice' => true, 'status' => Conta::STATUS_BLOQUEADA]);
            $conta->cliente?->update(['status' => Cliente::STATUS_BLOQUEADO]);
            $this->registrar($conta, 'CONTA_BLOQUEADA_FRAUDE', ['motivo' => $motivo]);
        });
    }

    private function alterar(Conta $conta, callable $alteracao): Conta
    {
        return DB::transaction(function () use ($conta, $alteracao) {
            $conta = Conta::query()->lockForUpdate()->findOrFail($conta->id);

            if ($conta->estaEncerrada() || $conta->tipo_conta === 'INSTITUCIONAL') {
                throw new RegraDeNegocioException('A conta atual não permite esta alteração.');
            }

            $alteracao($conta);

            return $conta;
        });
    }

    private function exigirKycPendente(Conta $conta): void
    {
        if (! in_array($conta->status_abertura, [Conta::ABERTURA_PENDENTE, Conta::ABERTURA_EM_ANALISE], true)) {
            throw new RegraDeNegocioException('O KYC já tem uma decisão registrada.');
        }
    }

    private function registrar(Conta $conta, string $evento, array $detalhes = []): void
    {
        app(AuditoriaService::class)->registrar('MOVIMENTACAO', $evento, $detalhes, 'contas', (string) $conta->id);
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
