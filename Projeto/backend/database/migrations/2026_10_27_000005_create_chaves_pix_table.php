<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** RF04 / RN42: chaves Pix com duplicidade rigorosa. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chaves_pix', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')->constrained()->cascadeOnDelete();
            $table->string('tipo_chave', 20);
            $table->string('valor_chave', 120)->unique();   // único em TODA a base
            $table->string('status', 20)->default('ATIVA')->index();
            $table->timestamp('registrado_em')->useCurrent();
            $table->timestamp('desativada_em')->nullable();
            $table->timestamps();

            $table->index(['conta_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chaves_pix');
    }
};
