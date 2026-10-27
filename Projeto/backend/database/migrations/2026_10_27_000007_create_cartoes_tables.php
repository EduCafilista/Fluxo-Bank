<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** RF07/RF12/RF19-RF21 + RN38-RN40: cartões lógico, físico e virtual. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cartoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')->constrained()->cascadeOnDelete();
            $table->string('modalidade', 10);           // DEBITO | CREDITO
            $table->string('numero_mascarado', 25);
            $table->date('validade');
            $table->string('cvv_hash', 64);
            $table->string('status', 20)->default('ATIVO')->index();
            $table->unsignedBigInteger('limite_aprovado_centavos')->default(200000);
            $table->unsignedBigInteger('limite_atual_centavos')->default(200000);
            $table->unsignedTinyInteger('dia_fechamento_fatura')->default(1);
            $table->timestamps();
        });

        Schema::create('cartoes_fisicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cartao_id')->unique()->constrained('cartoes')->cascadeOnDelete();
            $table->date('data_emissao');
            $table->date('data_entrega')->nullable();
            $table->string('endereco_entrega', 190)->nullable();
            // RN39: default seguro — compra on-line só se liberada explicitamente
            $table->boolean('permite_compras_online')->default(false);
            $table->timestamps();
        });

        Schema::create('cartoes_virtuais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cartao_id')->unique()->constrained('cartoes')->cascadeOnDelete();
            // RN38: todo virtual nasce de um físico obrigatoriamente
            $table->foreignId('cartao_fisico_id')->constrained('cartoes_fisicos')->cascadeOnDelete();
            $table->timestamp('gerado_em')->useCurrent();
            $table->timestamp('expira_em');
            $table->timestamps();
        });

        // RF21 / RN40: read model de recorrências detectadas no cartão
        Schema::create('assinaturas_cartao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cartao_id')->constrained('cartoes')->cascadeOnDelete();
            $table->foreignId('conta_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transacao_origem_id')->nullable()->constrained('transacoes')->nullOnDelete();
            $table->string('identificador_estabelecimento', 120);
            $table->string('nome_estabelecimento', 120)->nullable();
            $table->string('periodicidade', 20)->default('MENSAL');
            $table->unsignedBigInteger('valor_estimado_centavos')->default(0);
            $table->date('ultima_cobranca')->nullable();
            $table->date('proxima_cobranca')->nullable();
            $table->string('status', 20)->default('ATIVA')->index();
            $table->string('origem', 20)->default('DETECTADA');
            $table->decimal('confianca', 4, 2)->default(0.5);
            $table->jsonb('metadados')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assinaturas_cartao');
        Schema::dropIfExists('cartoes_virtuais');
        Schema::dropIfExists('cartoes_fisicos');
        Schema::dropIfExists('cartoes');
    }
};
