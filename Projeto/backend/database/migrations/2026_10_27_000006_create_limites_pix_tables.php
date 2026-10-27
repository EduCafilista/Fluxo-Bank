<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** RF14/RF23 + RN27-RN30/RN42-RN43: política de limites e consumo por janela. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('limites_pix', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')->nullable()->constrained()->cascadeOnDelete(); // null = política global
            $table->unsignedBigInteger('limite_por_transacao')->default(500000);
            $table->unsignedBigInteger('limite_diario')->default(1000000);
            $table->unsignedSmallInteger('quantidade_diaria')->default(20);
            $table->unsignedBigInteger('limite_noturno_por_transacao')->default(100000);
            $table->unsignedBigInteger('limite_noturno_diario')->default(200000);
            $table->unsignedSmallInteger('quantidade_noturna')->default(5);
            $table->unsignedBigInteger('limite_sem_chave_por_transacao')->default(50000);
            $table->unsignedBigInteger('limite_sem_chave_diario')->default(100000);
            $table->time('hora_inicio_noturno')->default('20:00');
            $table->time('hora_fim_noturno')->default('06:00');
            $table->boolean('ativo')->default(true);
            $table->timestamp('vigente_desde')->useCurrent();
            $table->timestamp('vigente_ate')->nullable();
            $table->timestamps();

            $table->index(['conta_id', 'ativo']);
        });

        Schema::create('consumos_limites_pix', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')->constrained()->cascadeOnDelete();
            $table->foreignId('limite_pix_id')->nullable()->constrained('limites_pix')->nullOnDelete();
            $table->date('data_referencia');
            $table->string('janela', 10);          // DIURNA | NOTURNA
            $table->unsignedBigInteger('valor_utilizado')->default(0);
            $table->unsignedSmallInteger('quantidade_utilizada')->default(0);
            $table->unsignedBigInteger('valor_reservado')->default(0);
            $table->unsignedSmallInteger('quantidade_reservada')->default(0);
            $table->timestamps();

            $table->unique(['conta_id', 'data_referencia', 'janela']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumos_limites_pix');
        Schema::dropIfExists('limites_pix');
    }
};
