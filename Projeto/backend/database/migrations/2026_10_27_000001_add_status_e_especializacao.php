<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RF10/RF11/RF13/RF18 + RN37: especialização de clientes (PF/PJ),
 * status da conta (operacional e KYC), bloqueio e múltiplas contas por cliente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('perfil', 20)->default('CLIENTE')->index();   // RBAC (RF09)
            $table->boolean('dois_fatores_ativo')->default(false);        // RF02
            $table->unsignedSmallInteger('tentativas_login')->default(0); // RN19
            $table->timestamp('bloqueado_ate')->nullable();
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->string('tipo_cliente', 2)->default('PF')->index();
            $table->string('razao_social', 150)->nullable();
            $table->char('cnpj', 14)->nullable()->unique();
            $table->string('representante_legal', 150)->nullable();
            $table->string('status', 20)->default('PENDENTE')->index();
        });

        // PJ não tem CPF nem data de nascimento: as colunas passam a aceitar NULL
        Schema::table('clientes', function (Blueprint $table) {
            $table->char('cpf', 11)->nullable()->change();
            $table->date('data_nascimento')->nullable()->change();
        });

        Schema::table('contas', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->string('tipo_conta', 20)->default('CORRENTE')->index();     // RF11
            $table->string('agencia', 10)->default('0001');
            $table->string('status', 20)->default('PENDENTE')->index();          // RF18
            $table->string('status_abertura', 20)->default('PENDENTE')->index(); // RF18 (KYC)
            $table->timestamp('status_abertura_atualizado_em')->nullable();
            $table->boolean('bloqueada')->default(false);                        // RF13
            $table->boolean('restricao_saque')->default(false);
            $table->date('aniversario_rendimento')->nullable();
        });

        // Um cliente pode ter várias contas (DER: CLIENTES 1..* CONTAS)
        Schema::table('contas', function (Blueprint $table) {
            $table->dropUnique('contas_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('contas', function (Blueprint $table) {
            $table->unique('user_id');
            $table->dropConstrainedForeignId('cliente_id');
            $table->dropColumn([
                'tipo_conta', 'agencia', 'status', 'status_abertura',
                'status_abertura_atualizado_em', 'bloqueada', 'restricao_saque', 'aniversario_rendimento',
            ]);
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['tipo_cliente', 'razao_social', 'cnpj', 'representante_legal', 'status']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['perfil', 'dois_fatores_ativo', 'tentativas_login', 'bloqueado_ate']);
        });
    }
};
