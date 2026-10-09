<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chaves_pix', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->foreignUuid('conta_id')->constrained('contas');
            
            $table->string('tipo_chave'); // CPF, EMAIL, CELULAR, ALEATORIA
<<<<<<< HEAD
            $table->string('valor_chave')->unique(); // Requisito de duplicidade no ecossistema
=======
            $table->string('valor_chave');
>>>>>>> e217f45e01ed74ee54a7c834faeb5061dee4afc3
            $table->timestamp('registrado_em')->useCurrent();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chaves_pix');
    }
};
