<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION public.fn_auditoria_sanitizar(dados jsonb)
            RETURNS jsonb LANGUAGE sql IMMUTABLE AS $fn$
                SELECT CASE WHEN dados IS NULL THEN NULL ELSE
                    (SELECT COALESCE(jsonb_object_agg(k,
                        CASE
                            WHEN lower(k) ~ '(password|senha|token|secret|segredo|codigo|cvv|authorization|api_key|chave_privada|pin|otp|hash|salt)'
                                THEN to_jsonb('[PROTEGIDO]'::text)
                            WHEN lower(k) IN ('cpf','cnpj','numero_cartao','cartao','documento')
                                THEN to_jsonb('[MASCARADO]'::text)
                            ELSE v END), '{}'::jsonb)
                       FROM jsonb_each(dados) AS x(k,v))
                END;
            $fn$;
        SQL);
    }

    public function down(): void
    {
        // Não reduzir a proteção de dados em rollback.
    }
};
