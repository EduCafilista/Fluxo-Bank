<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** RF24 + RN44: aviso de viagem sempre amarrado a UM cartão. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avisos_viagem', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cartao_id')->constrained('cartoes')->cascadeOnDelete(); // obrigatório (RN44)
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->jsonb('localidades');
            $table->string('status', 20)->default('ATIVO')->index();
            $table->timestamp('cancelado_em')->nullable();
            $table->timestamps();

            $table->index(['cartao_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avisos_viagem');
    }
};
