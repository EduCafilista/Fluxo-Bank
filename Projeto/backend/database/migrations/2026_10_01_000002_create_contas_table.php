<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->foreignUuid('cliente_id')->constrained('clientes');
            
            $table->string('tipo_conta'); // STI (Corrente, Poupanca, Salario, PJ)
            
            $table->string('agencia');
            $table->string('numero')->unique();
            $table->string('status')->default('ATIVA');
            $table->boolean('bloqueada')->default(false);
            $table->string('cnpj_vinculado')->nullable();
            $table->date('aniversario_rendimento')->nullable();
            $table->boolean('restricao_saque')->default(false);
            
            // RN02: NÃO criar coluna saldo, o saldo é derivado dos lançamentos.
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contas');
    }
};
