<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lancamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transacao_id')->constrained('transacoes')->cascadeOnDelete();
            $table->foreignId('conta_id')->constrained('contas')->cascadeOnDelete();
            $table->enum('tipo', ['debito', 'credito']);
            $table->unsignedBigInteger('valor_centavos');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lancamentos');
    }
};
