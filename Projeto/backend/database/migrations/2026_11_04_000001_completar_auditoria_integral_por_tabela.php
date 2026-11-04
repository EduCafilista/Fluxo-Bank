<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Complemento incremental: inclui compras_debito_simuladas e outras tabelas
 * operacionais ainda sem audit_*, sem apagar históricos anteriores.
 * Snapshots integrais preservam 100% das colunas; snapshots de consulta são mascarados.
 * ATENÇÃO: snapshots integrais contêm dados sensíveis; restringir acesso no PostgreSQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            throw new RuntimeException('Auditoria por trigger exige PostgreSQL.');
        }

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION public.fn_auditoria_por_tabela()
            RETURNS trigger LANGUAGE plpgsql SECURITY INVOKER AS $fn$
            DECLARE
                v_antes_integral jsonb;
                v_depois_integral jsonb;
                v_antes jsonb;
                v_depois jsonb;
                v_registro_id text;
                v_destino text;
            BEGIN
                v_destino := format('%I.%I', TG_TABLE_SCHEMA, 'audit_' || TG_TABLE_NAME);
                IF TG_OP <> 'INSERT' THEN
                    v_antes_integral := to_jsonb(OLD);
                    v_antes := public.fn_auditoria_sanitizar(v_antes_integral);
                END IF;
                IF TG_OP <> 'DELETE' THEN
                    v_depois_integral := to_jsonb(NEW);
                    v_depois := public.fn_auditoria_sanitizar(v_depois_integral);
                END IF;
                v_registro_id := COALESCE(v_depois_integral->>'id', v_antes_integral->>'id');

                EXECUTE format('INSERT INTO %s
                    (registro_id, operacao, usuario_id, request_id, antes, depois, diff,
                     antes_integral, depois_integral, registrado_em)
                    VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, clock_timestamp())', v_destino)
                USING v_registro_id, TG_OP,
                    CASE WHEN nullif(current_setting('fluxo.usuario_id', true), '') ~ '^[0-9]+$'
                         THEN nullif(current_setting('fluxo.usuario_id', true), '')::bigint ELSE NULL END,
                    CASE WHEN nullif(current_setting('fluxo.request_id', true), '') ~
                         '^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$'
                         THEN nullif(current_setting('fluxo.request_id', true), '')::uuid ELSE NULL END,
                    v_antes, v_depois,
                    CASE WHEN TG_OP = 'UPDATE' THEN public.fn_jsonb_diff(v_antes, v_depois) ELSE NULL END,
                    v_antes_integral, v_depois_integral;
                RETURN NULL;
            END;
            $fn$;
        SQL);

        // Descobre tabelas reais, evitando lista fixa que não acompanha novas migrations.
        $tables = DB::select(<<<'SQL'
            SELECT c.relname AS nome
            FROM pg_class c
            JOIN pg_namespace n ON n.oid = c.relnamespace
            WHERE n.nspname = 'public' AND c.relkind IN ('r', 'p')
              AND c.relname NOT LIKE 'audit\_%' ESCAPE '\'
              AND c.relname <> 'logs_auditoria'
            ORDER BY c.relname
        SQL);

        foreach ($tables as $row) {
            $table = $row->nome;
            $audit = 'audit_' . $table;
            $qt = '"' . str_replace('"', '""', $table) . '"';
            $qa = '"' . str_replace('"', '""', $audit) . '"';
            $exists = DB::selectOne('SELECT to_regclass(?) AS nome', ['public.' . $audit]);
            if (!$exists || $exists->nome === null) {
                DB::unprepared("CREATE TABLE public.{$qa} (
                    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
                    registro_id TEXT NULL,
                    operacao VARCHAR(6) NOT NULL CHECK (operacao IN ('INSERT','UPDATE','DELETE')),
                    usuario_id BIGINT NULL, request_id UUID NULL,
                    antes JSONB NULL, depois JSONB NULL, diff JSONB NULL,
                    antes_integral JSONB NULL, depois_integral JSONB NULL,
                    registrado_em TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp()
                )");
                DB::unprepared("CREATE INDEX ON public.{$qa} (registro_id, registrado_em)");
                DB::unprepared("CREATE INDEX ON public.{$qa} (registrado_em)");
            } else {
                // Adiciona campos sem modificar ou eliminar logs anteriores.
                DB::unprepared("ALTER TABLE public.{$qa}
                    ADD COLUMN IF NOT EXISTS antes_integral JSONB NULL,
                    ADD COLUMN IF NOT EXISTS depois_integral JSONB NULL");
            }
            DB::unprepared("DROP TRIGGER IF EXISTS tg_bloquear_auditoria ON public.{$qa}");
            DB::unprepared("CREATE TRIGGER tg_bloquear_auditoria
                BEFORE UPDATE OR DELETE ON public.{$qa}
                FOR EACH ROW EXECUTE FUNCTION public.fn_bloquear_mutacao_auditoria()");
            DB::unprepared("DROP TRIGGER IF EXISTS tg_bloquear_truncate_auditoria ON public.{$qa}");
            DB::unprepared("CREATE TRIGGER tg_bloquear_truncate_auditoria
                BEFORE TRUNCATE ON public.{$qa}
                FOR EACH STATEMENT EXECUTE FUNCTION public.fn_bloquear_mutacao_auditoria()");
            DB::unprepared("DROP TRIGGER IF EXISTS tg_auditoria_fisica_{$table} ON public.{$qt}");
            DB::unprepared("CREATE TRIGGER tg_auditoria_fisica_{$table}
                AFTER INSERT OR UPDATE OR DELETE ON public.{$qt}
                FOR EACH ROW EXECUTE FUNCTION public.fn_auditoria_por_tabela()");
            DB::unprepared("REVOKE UPDATE, DELETE, TRUNCATE ON public.{$qa} FROM PUBLIC");
            DB::unprepared("REVOKE SELECT (antes_integral, depois_integral) ON public.{$qa} FROM PUBLIC");
            // Evita dupla captura em logs_auditoria (auditoria central é de eventos).
            DB::unprepared("DROP TRIGGER IF EXISTS tg_auditoria_{$table} ON public.{$qt}");
        }
    }

    public function down(): void
    {
        // Histórico de auditoria não é descartado nem desativado por rollback.
        throw new RuntimeException('Rollback destrutivo/desativação da auditoria não permitido.');
    }
};
