<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Plano;
use Illuminate\Database\Seeder;

/** Catálogo de planos e itens (idempotente). */
class CatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $planos = [
            ['nome' => 'Fluxo Essencial', 'mensalidade_centavos' => 0, 'limite_retiradas' => 4, 'ativo' => true],
            ['nome' => 'Fluxo Mais', 'mensalidade_centavos' => 1990, 'limite_retiradas' => 20, 'ativo' => true],
            ['nome' => 'Fluxo Premium', 'mensalidade_centavos' => 4990, 'limite_retiradas' => 100, 'ativo' => true],
        ];

        foreach ($planos as $plano) {
            Plano::updateOrCreate(['nome' => $plano['nome']], $plano);
        }

        $itens = [
            ['nome' => 'Cartão adicional', 'valor_unitario_centavos' => 990],
            ['nome' => 'Seguro de conta', 'valor_unitario_centavos' => 1490],
            ['nome' => 'Notificação prioritária', 'valor_unitario_centavos' => 490],
        ];

        foreach ($itens as $item) {
            Item::updateOrCreate(['nome' => $item['nome']], $item);
        }

        $this->command?->info('Catálogo: '.count($planos).' planos e '.count($itens).' itens garantidos.');
    }
}
