<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RNF17 / RN45 / CLS-03: tipo, status e chave de idempotência da transação.
 * A unique em chave_idempotencia é o que impede debitar duas vezes no reenvio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transacoes', function (Blueprint $table) {
            $table->string('tipo', 20)->default('TED_INTERNA')->index();
            $table->string('status', 20)->default('EFETIVADA')->index();
            $table->string('chave_idempotencia', 120)->nullable()->unique();
        });

        Schema::table('lancamentos', function (Blueprint $table) {
            $table->string('descricao', 120)->nullable();
            $table->index(['conta_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('transacoes', function (Blueprint $table) {
            $table->dropUnique(['chave_idempotencia']);
            $table->dropColumn(['tipo', 'status', 'chave_idempotencia']);
        });

        Schema::table('lancamentos', function (Blueprint $table) {
            $table->dropIndex(['conta_id', 'created_at']);
            $table->dropColumn('descricao');
        });
    }
};
