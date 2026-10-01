<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lancamentos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->foreignUuid('transacao_id')->constrained('transacoes');
            $table->foreignUuid('conta_id')->constrained('contas');
            
            $table->string('natureza'); // DEBITO ou CREDITO
            $table->decimal('valor', 18, 2);
            
            // RN03 (Invariante Contábil): Não será imposta como check/trigger complexa aqui.
            // O domínio deve garantir que SUM(Lançamentos) de uma Transação seja zero.
            // TODO Futuro: Adicionar validação de negócio/trigger para partidas dobradas (RN03).

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lancamentos');
    }
};
