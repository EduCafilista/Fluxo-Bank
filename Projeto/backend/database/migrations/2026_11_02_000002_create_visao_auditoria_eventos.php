<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE VIEW public.vw_auditoria_eventos AS
            SELECT id, registrado_em, operacao AS evento, categoria,
                   usuario_id, request_id,
                   CASE WHEN tabela = 'http' THEN 'API' ELSE 'SISTEMA' END AS origem,
                   CASE WHEN status_http >= 400 THEN 'FALHA'
                        WHEN status_http IS NOT NULL THEN 'SUCESSO'
                        ELSE 'NAO_INFORMADO' END AS resultado,
                   jsonb_strip_nulls(jsonb_build_object(
                       'tabela', NULLIF(tabela, 'http'),
                       'registro_id', registro_id,
                       'rota', rota,
                       'metodo', metodo,
                       'status_http', status_http,
                       'ip', ip_address,
                       'dados', payload
                   )) AS detalhes
              FROM public.logs_auditoria;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS public.vw_auditoria_eventos');
    }
};
