<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RF-SEG01/02/03/04 e RF22: trilha de auditoria única do sistema.
 *  - escrita por triggers PostgreSQL (INSERT/UPDATE/DELETE de domínio);
 *  - escrita pelo middleware AuditoriaRequisicao (ACESSO/CONSULTA);
 *  - append-only: UPDATE e DELETE são bloqueados no próprio banco.
 *
 * O id é BIGSERIAL (e não UUID) porque a trilha usa hash encadeado:
 * é preciso uma ordem total e crescente dos registros.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs_auditoria', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('tabela', 60)->index();
            $table->string('registro_id', 64)->nullable();
            $table->string('operacao', 40)->index();                 // INSERT/UPDATE/DELETE/HTTP/CHAVE_PIX_CRIADA...
            $table->string('categoria', 20)->index();                // ACESSO/CONSULTA/MOVIMENTACAO/SISTEMA
            $table->foreignId('usuario_id')->nullable()->index();    // sem FK: usuário pode ser apagado
            $table->uuid('request_id')->nullable()->index();
            $table->string('rota', 190)->nullable();
            $table->string('metodo', 10)->nullable();
            $table->unsignedSmallInteger('status_http')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->jsonb('payload')->nullable();
            $table->jsonb('antes')->nullable();
            $table->jsonb('depois')->nullable();
            $table->jsonb('diff')->nullable();
            $table->string('hash_anterior', 64)->nullable();
            $table->string('hash_registro', 64)->nullable();
            $table->timestampTz('registrado_em')->useCurrent();

            $table->index(['registrado_em']);
            $table->index(['usuario_id', 'categoria']);
        });

        // Dados sensíveis nunca chegam à trilha (RNF13 / LGPD)
        // pgcrypto fornece digest()/gen_random_uuid() usados nos hashes da trilha
        DB::unprepared('CREATE EXTENSION IF NOT EXISTS pgcrypto;');

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_auditoria_sanitizar(dados jsonb) RETURNS jsonb AS $$
                SELECT CASE WHEN dados IS NULL THEN NULL ELSE
                    dados - 'password' - 'remember_token' - 'cvv' - 'cvv_hash'
                          - 'token' - 'codigo_hash' - 'codigo' - 'api_key'
                END;
            $$ LANGUAGE sql IMMUTABLE;

            -- Diff entre OLD e NEW (RF-SEG02: "diff em coluna estruturada JSONB")
            CREATE OR REPLACE FUNCTION fn_jsonb_diff(antes jsonb, depois jsonb) RETURNS jsonb AS $$
                SELECT COALESCE(jsonb_object_agg(k, jsonb_build_object('antes', antes->k, 'depois', depois->k)), '{}'::jsonb)
                FROM (
                    SELECT jsonb_object_keys(COALESCE(antes, '{}'::jsonb)) AS k
                    UNION
                    SELECT jsonb_object_keys(COALESCE(depois, '{}'::jsonb)) AS k
                ) chaves
                WHERE (antes->k) IS DISTINCT FROM (depois->k);
            $$ LANGUAGE sql IMMUTABLE;

            CREATE OR REPLACE FUNCTION fn_auditoria() RETURNS trigger AS $$
            DECLARE
                v_antes   jsonb;
                v_depois  jsonb;
                v_hash_anterior text;
                v_hash    text;
            BEGIN
                IF TG_OP IN ('UPDATE', 'DELETE') THEN
                    v_antes := fn_auditoria_sanitizar(to_jsonb(OLD));
                END IF;

                IF TG_OP IN ('INSERT', 'UPDATE') THEN
                    v_depois := fn_auditoria_sanitizar(to_jsonb(NEW));
                END IF;

                SELECT hash_registro INTO v_hash_anterior
                  FROM logs_auditoria ORDER BY id DESC LIMIT 1;

                v_hash := encode(digest(
                    COALESCE(v_hash_anterior, '') || TG_TABLE_NAME || TG_OP ||
                    COALESCE(to_jsonb(COALESCE(NEW, OLD))::text, ''), 'sha256'), 'hex');

                INSERT INTO logs_auditoria (
                    tabela, registro_id, operacao, categoria,
                    usuario_id, request_id, antes, depois, diff, hash_anterior, hash_registro
                ) VALUES (
                    TG_TABLE_NAME,
                    COALESCE(NEW.id, OLD.id)::text,
                    TG_OP,
                    'MOVIMENTACAO',
                    NULLIF(current_setting('fluxo.usuario_id', true), '')::bigint,
                    NULLIF(current_setting('fluxo.request_id', true), '')::uuid,
                    v_antes,
                    v_depois,
                    CASE WHEN TG_OP = 'UPDATE' THEN fn_jsonb_diff(v_antes, v_depois) END,
                    v_hash_anterior,
                    v_hash
                );

                RETURN COALESCE(NEW, OLD);
            END;
            $$ LANGUAGE plpgsql;

            -- RF-SEG03: append-only. Nem UPDATE nem DELETE passam.
            CREATE OR REPLACE RULE logs_auditoria_sem_update AS
                ON UPDATE TO logs_auditoria DO INSTEAD NOTHING;
            CREATE OR REPLACE RULE logs_auditoria_sem_delete AS
                ON DELETE TO logs_auditoria DO INSTEAD NOTHING;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP RULE IF EXISTS logs_auditoria_sem_update ON logs_auditoria;
            DROP RULE IF EXISTS logs_auditoria_sem_delete ON logs_auditoria;
            DROP FUNCTION IF EXISTS fn_auditoria();
            DROP FUNCTION IF EXISTS fn_jsonb_diff(jsonb, jsonb);
            DROP FUNCTION IF EXISTS fn_auditoria_sanitizar(jsonb);
        SQL);

        Schema::dropIfExists('logs_auditoria');
    }
};
