<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Em produção este seeder só cria o catálogo (planos/itens), que é dado de
 * referência. Usuários de teste ficam no DemoSeeder, chamado apenas em local:
 *
 *   php artisan db:seed                    → catálogo apenas (seguro)
 *   php artisan db:seed --class=DemoSeeder → dados de demonstração
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CatalogoSeeder::class);
    }
}
