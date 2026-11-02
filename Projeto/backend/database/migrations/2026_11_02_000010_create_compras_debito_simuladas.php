<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('compras_debito_simuladas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cartao_id')->constrained('cartoes')->restrictOnDelete();
            $table->foreignId('transacao_id')->unique()->constrained('transacoes')->restrictOnDelete();
            $table->unsignedBigInteger('valor_centavos');
            $table->string('estabelecimento', 120);
            $table->boolean('online')->default(false);
            $table->string('chave_idempotencia', 71)->unique();
            $table->string('fingerprint', 64);
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras_debito_simuladas');
    }
};
