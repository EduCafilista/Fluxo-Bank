<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cobrancas_pix', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')->constrained('contas')->restrictOnDelete();
            $table->foreignId('transacao_id')->nullable()->unique()->constrained('transacoes')->restrictOnDelete();
            $table->string('txid', 25)->unique();
            $table->char('hash_payload', 64)->unique();
            $table->unsignedBigInteger('valor_centavos');
            $table->timestamp('expira_em');
            $table->timestamps();
        });

        DB::statement('CREATE TRIGGER trg_aud_cobrancas_pix AFTER INSERT OR UPDATE OR DELETE ON cobrancas_pix FOR EACH ROW EXECUTE FUNCTION fn_auditoria()');
    }

    public function down(): void
    {
        Schema::dropIfExists('cobrancas_pix');
    }
};
