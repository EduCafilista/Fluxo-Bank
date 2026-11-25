<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('contas', function (Blueprint $table) {
            $table->boolean('bloqueio_backoffice')->default(false);
        });

        // Bloqueios anteriores por fraude já deixavam o cadastro do titular bloqueado.
        DB::table('contas')
            ->where('status', 'BLOQUEADA')
            ->where('status_abertura', 'APROVADA')
            ->whereIn('cliente_id', DB::table('clientes')->where('status', 'BLOQUEADO')->select('id'))
            ->update(['bloqueio_backoffice' => true]);
    }

    public function down(): void
    {
        Schema::table('contas', fn (Blueprint $table) => $table->dropColumn('bloqueio_backoffice'));
        Schema::table('users', fn (Blueprint $table) => $table->dropSoftDeletes());
    }
};
