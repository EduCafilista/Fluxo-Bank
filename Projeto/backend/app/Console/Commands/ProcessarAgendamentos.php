<?php

namespace App\Console\Commands;

use App\Models\Agendamento;
use App\Services\AgendamentoService;
use Illuminate\Console\Command;

class ProcessarAgendamentos extends Command
{
    protected $signature = 'agendamentos:processar {--limite=50 : Quantos agendamentos processar por execução}';

    protected $description = 'Executa os agendamentos vencidos com lock e idempotência';

    public function handle(AgendamentoService $service): int
    {
        $expirados = $service->expirarAntigos();

        if ($expirados > 0) {
            $this->warn("{$expirados} agendamento(s) marcados como EXPIRADO.");
        }

        $pendentes = Agendamento::where('status', Agendamento::STATUS_AGENDADO)
            ->where('agendado_para', '<=', now())
            ->orderBy('agendado_para')
            ->limit((int) $this->option('limite'))
            ->get();

        if ($pendentes->isEmpty()) {
            $this->info('Nenhum agendamento vencido para executar.');

            return self::SUCCESS;
        }

        $executados = 0;
        $falhas = 0;

        foreach ($pendentes as $agendamento) {
            $status = $service->executar($agendamento);

            match ($status) {
                Agendamento::STATUS_EXECUTADO => $executados++,
                Agendamento::STATUS_FALHOU => $falhas++,
                default => null,
            };

            $this->line(sprintf(
                'Agendamento #%d → %s (tentativa %d)',
                $agendamento->id,
                $status,
                $agendamento->refresh()->tentativas,
            ));
        }

        $this->info("Executados: {$executados} | Falhas: {$falhas}");

        return self::SUCCESS;
    }
}
