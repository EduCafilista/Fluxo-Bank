<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** RF02 / RN19-RN20: desafio de 2FA com hash, validade, uso único e tentativas. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('codigos_verificacao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('canal', 20)->default('EMAIL');
            $table->string('finalidade', 30)->default('LOGIN');
            $table->string('codigo_hash', 64);
            $table->timestamp('expira_em');
            $table->unsignedTinyInteger('tentativas')->default(0);
            $table->unsignedTinyInteger('max_tentativas')->default(3);
            $table->timestamp('utilizado_em')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'finalidade', 'utilizado_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('codigos_verificacao');
    }
};
