<?php

namespace App\Console\Commands;

use App\Models\Assinatura;
use App\Services\AssinaturaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GerarCobrancasAssinaturas extends Command
{
    protected $signature = 'assinaturas:cobrar';

    protected $description = 'Gera as cobranças da competência atual para assinaturas ativas';

    public function handle(AssinaturaService $servico): int
    {
        Assinatura::where('status', Assinatura::STATUS_ATIVA)
            ->select('id')
            ->eachById(function (Assinatura $assinatura) use ($servico) {
                DB::transaction(function () use ($assinatura, $servico) {
                    $atual = Assinatura::query()->lockForUpdate()->find($assinatura->id);

                    if ($atual?->status === Assinatura::STATUS_ATIVA) {
                        $servico->gerarCobrancaProporcional($atual);
                    }
                });
            });

        return self::SUCCESS;
    }
}
