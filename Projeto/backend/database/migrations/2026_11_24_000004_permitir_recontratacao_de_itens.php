<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itens_assinatura', fn (Blueprint $table) => $table->dropUnique(['assinatura_id', 'item_id']));
        DB::statement('CREATE UNIQUE INDEX itens_assinatura_item_ativo ON itens_assinatura (assinatura_id, item_id) WHERE data_exclusao IS NULL');
    }

    public function down(): void
    {
        Schema::table('itens_assinatura', fn (Blueprint $table) => $table->unique(['assinatura_id', 'item_id']));
        DB::statement('DROP INDEX itens_assinatura_item_ativo');
    }
};
