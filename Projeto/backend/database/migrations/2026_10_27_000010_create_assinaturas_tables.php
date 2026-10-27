<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Planos, assinaturas, itens, cobranças e chamados de suporte (RF09/RF21). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 60);
            $table->unsignedBigInteger('mensalidade_centavos');
            $table->unsignedSmallInteger('limite_retiradas')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('itens', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 80);
            $table->unsignedBigInteger('valor_unitario_centavos');
            $table->timestamps();
        });

        Schema::create('assinaturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plano_id')->constrained('planos')->restrictOnDelete();
            $table->date('data_inicio');
            $table->date('data_fim')->nullable();
            $table->unsignedTinyInteger('dia_cobranca')->default(1);
            $table->string('status', 20)->default('ATIVA')->index();
            $table->unsignedBigInteger('valor_atual_centavos')->default(0);
            $table->timestamps();
        });

        Schema::create('itens_assinatura', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assinatura_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('itens')->restrictOnDelete();
            // RN22: retirada é lógica, nunca DELETE
            $table->date('data_exclusao')->nullable();
            $table->string('motivo_exclusao', 190)->nullable();
            $table->timestamps();

            $table->unique(['assinatura_id', 'item_id']);
        });

        Schema::create('cobrancas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assinatura_id')->constrained()->cascadeOnDelete();
            $table->string('competencia', 7);       // AAAA-MM
            $table->unsignedBigInteger('valor_centavos');
            $table->date('vencimento');
            $table->string('status', 20)->default('ABERTA')->index();
            $table->timestamps();

            $table->unique(['assinatura_id', 'competencia']);
        });

        Schema::create('chamados_suporte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('assunto', 120);
            $table->text('mensagem');
            $table->string('status', 20)->default('ABERTO')->index();
            $table->text('resposta')->nullable();
            $table->foreignId('atendente_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('respondido_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chamados_suporte');
        Schema::dropIfExists('cobrancas');
        Schema::dropIfExists('itens_assinatura');
        Schema::dropIfExists('assinaturas');
        Schema::dropIfExists('itens');
        Schema::dropIfExists('planos');
    }
};
