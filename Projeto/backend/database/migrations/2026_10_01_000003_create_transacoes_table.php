<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transacoes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->string('tipo'); // EX: PIX, TED, DOC, BOLETO
            $table->string('status')->default('PENDENTE'); // PENDENTE, CONCLUIDA, FALHOU
            $table->string('chave_idempotencia')->unique()->nullable();
            
            $table->timestamp('criado_em')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transacoes');
    }
};
