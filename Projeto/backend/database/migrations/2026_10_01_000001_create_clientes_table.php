<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Relacionamento 1:1 com users invertido do DER para evitar dependência circular 
            // e acomodar o default BigInt do Laravel
            $table->foreignId('user_id')->unique()->constrained('users');
            
            // STI conforme RF10
            $table->string('tipo_cliente'); // PF ou PJ
            
            $table->string('nome')->nullable();
            $table->string('razao_social')->nullable();
            $table->string('cpf')->unique()->nullable();
            $table->string('cnpj')->unique()->nullable();
            $table->date('data_nascimento')->nullable();
            $table->string('representante_legal')->nullable();
            $table->string('status')->default('ATIVO');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
