<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TABELAS = [
        'users', 'clientes', 'contas', 'transacoes', 'lancamentos', 'chaves_pix',
        'limites_pix', 'consumos_limites_pix', 'cartoes', 'cartoes_fisicos', 'cartoes_virtuais',
        'assinaturas_cartao', 'agendamentos', 'avisos_viagem', 'planos', 'assinaturas',
        'itens', 'itens_assinatura', 'cobrancas', 'chamados_suporte', 'compras_debito_simuladas',
        'codigos_verificacao',
    ];

    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION public.fn_auditoria_sanitizar(dados jsonb)
            RETURNS jsonb LANGUAGE sql IMMUTABLE AS $fn$
                SELECT CASE jsonb_typeof(dados)
                    WHEN 'object' THEN (
                        SELECT COALESCE(jsonb_object_agg(k,
                            CASE
                                WHEN v = 'null'::jsonb THEN v
                                WHEN lower(k) ~ '(password|senha|token|secret|segredo|codigo|cvv|authorization|api_key|chave_privada|pin|otp|hash|salt|payload_emv|linha_digitavel)'
                                    THEN to_jsonb('[PROTEGIDO]'::text)
                                WHEN lower(k) IN (
                                    'cpf','cnpj','numero_cartao','cartao','documento','email','name','nome',
                                    'razao_social','representante_legal','data_nascimento','telefone','celular',
                                    'valor_chave','endereco','endereco_entrega','payload','data',
                                    'descricao','motivo','resposta','mensagem','assunto','erro','estabelecimento'
                                ) THEN to_jsonb('[MASCARADO]'::text)
                                ELSE public.fn_auditoria_sanitizar(v)
                            END), '{}'::jsonb)
                        FROM jsonb_each(dados) AS atributos(k,v)
                    )
                    WHEN 'array' THEN (
                        SELECT COALESCE(jsonb_agg(public.fn_auditoria_sanitizar(v) ORDER BY pos), '[]'::jsonb)
                        FROM jsonb_array_elements(dados) WITH ORDINALITY AS elementos(v,pos)
                    )
                    ELSE dados
                END;
            $fn$;

            CREATE OR REPLACE FUNCTION public.fn_auditoria_por_tabela()
            RETURNS trigger LANGUAGE plpgsql SECURITY INVOKER AS $fn$
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
                    CASE WHEN TG_OP = 'UPDATE' THEN public.fn_jsonb_diff(v_antes, v_depois) END;
                RETURN NULL;
            END;
            $fn$;

            CREATE OR REPLACE FUNCTION public.fn_auditoria_bloquear_truncate()
            RETURNS trigger LANGUAGE plpgsql AS $fn$
            BEGIN
                RAISE EXCEPTION 'A trilha de auditoria é append-only.';
            END;
            $fn$;

            DROP TRIGGER IF EXISTS trg_logs_auditoria_sem_truncate ON public.logs_auditoria;
            CREATE TRIGGER trg_logs_auditoria_sem_truncate BEFORE TRUNCATE ON public.logs_auditoria
            FOR EACH STATEMENT EXECUTE FUNCTION public.fn_auditoria_bloquear_truncate();
        SQL);

        foreach (self::TABELAS as $tabela) {
            DB::statement("CREATE TRIGGER tg_auditoria_central_{$tabela} AFTER INSERT OR UPDATE OR DELETE ON {$tabela}
                FOR EACH ROW EXECUTE FUNCTION public.fn_auditoria()");
        }
    }

    public function down(): void
    {
        foreach (self::TABELAS as $tabela) {
            DB::statement("DROP TRIGGER IF EXISTS tg_auditoria_central_{$tabela} ON {$tabela}");
        }

        // As funções de sanitização e o bloqueio de TRUNCATE seguem em vigor.
    }
};
