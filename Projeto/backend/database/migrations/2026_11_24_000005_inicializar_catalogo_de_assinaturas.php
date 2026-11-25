<?php

use Database\Seeders\CatalogoSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(CatalogoSeeder::class)->run();
    }

    public function down(): void
    {
        // O catálogo pode estar vinculado a assinaturas posteriores à instalação.
    }
};
