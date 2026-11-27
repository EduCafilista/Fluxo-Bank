<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cobrancas_pix', function (Blueprint $table) {
            $table->string('descricao', 120)->nullable();
            $table->text('payload_emv')->nullable();
            $table->timestamp('cancelada_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cobrancas_pix', function (Blueprint $table) {
            $table->dropColumn(['descricao', 'payload_emv', 'cancelada_em']);
        });
    }
};
