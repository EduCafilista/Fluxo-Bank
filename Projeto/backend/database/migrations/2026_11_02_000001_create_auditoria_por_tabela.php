<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Auditoria física por tabela. Mantém logs_auditoria para compatibilidade.
 * Não reconstitui alterações históricas anteriores à instalação.
 */
return new class extends Migration
{
    private const SCHEMA = 'public';

    // Todas as tabelas originais, exceto logs_auditoria (evita recursão).
    // Tabelas efêmeras podem gerar alto volume de histórico.
    private const TABLES = [
        'users', 'clientes', 'contas', 'transacoes', 'lancamentos',
        'chaves_pix', 'limites_pix', 'consumos_limites_pix',
        'cartoes', 'cartoes_fisicos', 'cartoes_virtuais', 'assinaturas_cartao',
        'agendamentos', 'avisos_viagem', 'planos', 'assinaturas', 'itens',
        'itens_assinatura', 'cobrancas', 'chamados_suporte',
        'codigos_verificacao', 'personal_access_tokens', 'password_reset_tokens',
        'cache', 'cache_locks', 'sessions', 'jobs', 'job_batches',
        'failed_jobs', 'migrations',
    ];

    public function up(): void
    {
        // DDL transacional do PostgreSQL: falhas não deixam auditoria parcial.
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION public.fn_auditoria_por_tabela() RETURNS trigger
            LANGUAGE plpgsql SECURITY INVOKER AS $fn$
            DECLARE
                v_antes jsonb;
                v_depois jsonb;
                v_registro_id text;
                v_destino text;
            BEGIN
                v_destino := format('%I.%I', TG_TABLE_SCHEMA, 'audit_' || TG_TABLE_NAME);
                IF TG_OP <> 'INSERT' THEN
                    v_antes := public.fn_auditoria_sanitizar(to_jsonb(OLD));
                END IF;
                IF TG_OP <> 'DELETE' THEN
                    v_depois := public.fn_auditoria_sanitizar(to_jsonb(NEW));
                END IF;
                v_registro_id := COALESCE(v_depois->>'id', v_antes->>'id');

                EXECUTE format('INSERT INTO %s
                    (registro_id, operacao, usuario_id, request_id, antes, depois, diff, registrado_em)
                    VALUES ($1, $2, $3, $4, $5, $6, $7, clock_timestamp())', v_destino)
                USING v_registro_id, TG_OP,
                    NULLIF(current_setting('fluxo.usuario_id', true), '')::bigint,
                    NULLIF(current_setting('fluxo.request_id', true), '')::uuid,
                    v_antes, v_depois,
                    CASE WHEN TG_OP = 'UPDATE' THEN public.fn_jsonb_diff(v_antes, v_depois) ELSE NULL END;
                RETURN NULL; -- AFTER ROW: retorno ignorado
            END;
            $fn$;

            CREATE OR REPLACE FUNCTION public.fn_bloquear_mutacao_auditoria() RETURNS trigger
            LANGUAGE plpgsql AS $fn$
            BEGIN
                RAISE EXCEPTION 'Tabela de auditoria imutavel: %', TG_TABLE_NAME
                    USING ERRCODE = '42501';
            END;
            $fn$;
        SQL);

        foreach (self::TABLES as $table) {
            // Não inventa tabelas: a migration só opera sobre tabelas existentes.
            $exists = DB::selectOne(
                'SELECT to_regclass(?) AS nome',
                [self::SCHEMA . '.' . $table]
            );
            if (!$exists || $exists->nome === null) {
                throw new RuntimeException("Tabela esperada não encontrada: {$table}");
            }

            $audit = 'audit_' . $table;
            $qt = '"' . str_replace('"', '""', $table) . '"';
            $qa = '"' . str_replace('"', '""', $audit) . '"';
            DB::unprepared("CREATE TABLE public.{$qa} (
                id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
                registro_id TEXT NULL,
                operacao VARCHAR(6) NOT NULL CHECK (operacao IN ('INSERT','UPDATE','DELETE')),
                usuario_id BIGINT NULL,
                request_id UUID NULL,
                antes JSONB NULL,
                depois JSONB NULL,
                diff JSONB NULL,
                registrado_em TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp()
            )");
            DB::unprepared("CREATE INDEX ON public.{$qa} (registro_id, registrado_em)");
            DB::unprepared("CREATE INDEX ON public.{$qa} (registrado_em)");
            DB::unprepared("CREATE TRIGGER tg_bloquear_auditoria
                BEFORE UPDATE OR DELETE ON public.{$qa}
                FOR EACH ROW EXECUTE FUNCTION public.fn_bloquear_mutacao_auditoria()");
            DB::unprepared("CREATE TRIGGER tg_bloquear_truncate_auditoria
                BEFORE TRUNCATE ON public.{$qa}
                FOR EACH STATEMENT EXECUTE FUNCTION public.fn_bloquear_mutacao_auditoria()");
            DB::unprepared("CREATE TRIGGER tg_auditoria_fisica_{$table}
                AFTER INSERT OR UPDATE OR DELETE ON public.{$qt}
                FOR EACH ROW EXECUTE FUNCTION public.fn_auditoria_por_tabela()");
            // PUBLIC não é o proprietário. Usar usuário de runtime sem privilégios DDL.
            DB::unprepared("REVOKE UPDATE, DELETE, TRUNCATE ON public.{$qa} FROM PUBLIC");
            // Desliga trigger legada duplicada: central fica somente com eventos da API.
            DB::unprepared("DROP TRIGGER IF EXISTS tg_auditoria_{$table} ON public.{$qt}");
        }
    }

    public function down(): void
    {
        // Reversão destrói o histórico criado: executar somente com autorização explícita.
        foreach (array_reverse(self::TABLES) as $table) {
            $qt = '"' . str_replace('"', '""', $table) . '"';
            $qa = '"audit_' . str_replace('"', '""', $table) . '"';
            DB::unprepared("DROP TRIGGER IF EXISTS tg_auditoria_fisica_{$table} ON public.{$qt}");
            // Intencionalmente não remove audit_* no rollback: histórico fiscal deve ser preservado.
        }
        // Funções mantidas porque as tabelas históricas permanecem.
        
    }
};
