<?php

namespace App\Services;

use App\Exceptions\RegraDeNegocioException;
use App\Models\Conta;
use App\Models\LimitePix;
use Illuminate\Support\Facades\DB;

/** RF14: o titular pode reduzir limites dentro dos tetos pré-aprovados da instituição. */
class ConfiguracaoLimitePixService
{
    public function ajustar(Conta $conta, int $porTransacao, int $diario, int $noturno): LimitePix
    {
        $tetos = config('fluxo.limites_pix');
        if ($porTransacao < 1 || $diario < 1 || $noturno < 1
            || $porTransacao > (int) $tetos['por_transacao']
            || $diario > (int) $tetos['diario']
            || $noturno > (int) $tetos['noturno_por_transacao']
            || $porTransacao > $diario || $noturno > $porTransacao) {
            throw new RegraDeNegocioException('Respeite os tetos aprovados. O limite noturno pode ser de até o limite por transação diurno, que deve caber no limite diário.');
        }

        return DB::transaction(function () use ($conta, $porTransacao, $diario, $noturno, $tetos) {
            $conta = Conta::query()->lockForUpdate()->findOrFail($conta->id);
            if ($conta->estaEncerrada() || $conta->tipo_conta === 'INSTITUCIONAL') {
                throw new RegraDeNegocioException('A conta atual não permite alteração de limites.');
            }

            LimitePix::where('conta_id', $conta->id)->where('ativo', true)
                ->update(['ativo' => false, 'vigente_ate' => now()]);
            $limite = LimitePix::create([
                'conta_id' => $conta->id,
                'limite_por_transacao' => $porTransacao,
                'limite_diario' => $diario,
                'quantidade_diaria' => (int) $tetos['quantidade_diaria'],
                'limite_noturno_por_transacao' => $noturno,
                'limite_noturno_diario' => min($diario, (int) $tetos['noturno_diario']),
                'quantidade_noturna' => (int) $tetos['quantidade_noturna'],
                'limite_sem_chave_por_transacao' => min($porTransacao, (int) $tetos['sem_chave_por_transacao']),
                'limite_sem_chave_diario' => min($diario, (int) $tetos['sem_chave_diario']),
                'hora_inicio_noturno' => $tetos['hora_inicio_noturno'],
                'hora_fim_noturno' => $tetos['hora_fim_noturno'],
                'ativo' => true,
                'vigente_desde' => now(),
            ]);
            app(AuditoriaService::class)->registrar(
                'MOVIMENTACAO', 'LIMITE_PIX_AJUSTADO', [
                    'conta_id' => $conta->id, 'limite_por_transacao' => $porTransacao,
                    'limite_diario' => $diario, 'limite_noturno_por_transacao' => $noturno,
                ], 'limites_pix', (string) $limite->id,
            );

            return $limite;
        });
    }
}
