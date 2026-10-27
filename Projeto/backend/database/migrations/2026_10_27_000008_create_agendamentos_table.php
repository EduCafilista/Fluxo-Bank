<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** RF06/RF25 + RN45: fila persistida com máquina de estados e idempotência. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agendamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transacao_id')->nullable()->constrained('transacoes')->nullOnDelete();
            $table->string('tipo_operacao', 20);
            $table->unsignedBigInteger('valor_centavos');
            $table->timestamp('agendado_para')->index();
            $table->jsonb('payload_operacao')->nullable();
            $table->string('status', 20)->default('AGENDADO')->index();
            $table->unsignedTinyInteger('tentativas')->default(0);
            $table->string('chave_idempotencia', 120)->unique();
            $table->boolean('recorrente')->default(false);
            $table->timestamp('proxima_execucao')->nullable();
            $table->text('erro')->nullable();
            $table->timestamps();

            $table->index(['status', 'agendado_para']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agendamentos');
    }
};
