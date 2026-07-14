-- Migration idempotente para recurso de Bots por unidade

SET search_path TO app;

CREATE TABLE IF NOT EXISTS app_bot (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    system_unit_id INT NOT NULL REFERENCES adianti.system_unit(id),
    nome VARCHAR(150) NOT NULL,
    evolution_instance VARCHAR(120) NOT NULL,
    evolution_instance_id VARCHAR(120),
    evolution_api_url TEXT,
    evolution_api_key TEXT,
    instrucoes TEXT,
    modelo_llm VARCHAR(80) NOT NULL DEFAULT 'phi3',
    temperatura NUMERIC(4,3) NOT NULL DEFAULT 0.200,
    usar_rag CHAR(1) NOT NULL DEFAULT 'Y' CHECK (usar_rag IN ('Y','N')),
    faq_top_k INT NOT NULL DEFAULT 5,
    similaridade_minima NUMERIC(5,4) NOT NULL DEFAULT 0.7500,
    ativo CHAR(1) NOT NULL DEFAULT 'Y' CHECK (ativo IN ('Y','N')),
    criado_em TIMESTAMP NOT NULL DEFAULT NOW(),
    atualizado_em TIMESTAMP NOT NULL DEFAULT NOW()
);

ALTER TABLE app.app_bot
    ADD COLUMN IF NOT EXISTS evolution_instance_id VARCHAR(120);

CREATE INDEX IF NOT EXISTS app_bot_unit_idx ON app_bot (system_unit_id);
CREATE INDEX IF NOT EXISTS app_bot_nome_idx ON app_bot (nome);

CREATE TABLE IF NOT EXISTS app_bot_faq (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    app_bot_id UUID NOT NULL REFERENCES app_bot(id) ON DELETE CASCADE,
    pergunta TEXT NOT NULL,
    resposta TEXT NOT NULL,
    palavras_chave TEXT,
    fonte_externa TEXT,
    embedding_text TEXT,
    embedding_array FLOAT4[],
    ordem INT NOT NULL DEFAULT 0,
    ativo CHAR(1) NOT NULL DEFAULT 'Y' CHECK (ativo IN ('Y','N')),
    criado_em TIMESTAMP NOT NULL DEFAULT NOW(),
    atualizado_em TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS app_bot_faq_bot_idx ON app_bot_faq (app_bot_id);
CREATE INDEX IF NOT EXISTS app_bot_faq_ordem_idx ON app_bot_faq (app_bot_id, ordem);

DO $$
DECLARE
    v_vector_schema TEXT;
BEGIN
    BEGIN
        CREATE EXTENSION IF NOT EXISTS vector;
    EXCEPTION
        WHEN OTHERS THEN
            RAISE NOTICE 'pgvector indisponivel (%); continuando sem coluna vetorial nativa', SQLERRM;
    END;

    SELECT n.nspname
      INTO v_vector_schema
      FROM pg_type t
      JOIN pg_namespace n ON n.oid = t.typnamespace
     WHERE t.typname = 'vector'
     ORDER BY CASE WHEN n.nspname = 'public' THEN 0 ELSE 1 END
     LIMIT 1;

    IF v_vector_schema IS NULL THEN
        RAISE NOTICE 'tipo vector nao encontrado; continuando sem coluna vetorial nativa';
    ELSIF NOT EXISTS (
        SELECT 1
          FROM information_schema.columns
         WHERE table_schema = 'app'
           AND table_name = 'app_bot_faq'
           AND column_name = 'embedding_vector'
    ) THEN
        EXECUTE format('ALTER TABLE app.app_bot_faq ADD COLUMN embedding_vector %I.vector(1024)', v_vector_schema);
    END IF;

    IF v_vector_schema IS NOT NULL
       AND EXISTS (
           SELECT 1
             FROM information_schema.columns
            WHERE table_schema = 'app'
              AND table_name = 'app_bot_faq'
              AND column_name = 'embedding_vector'
       )
       AND NOT EXISTS (
           SELECT 1
             FROM pg_indexes
            WHERE schemaname = 'app'
              AND indexname = 'app_bot_faq_embedding_ivfflat_idx'
       ) THEN
        EXECUTE format(
            'CREATE INDEX app_bot_faq_embedding_ivfflat_idx ON app.app_bot_faq USING ivfflat (embedding_vector %I.vector_cosine_ops) WITH (lists = 100)',
            v_vector_schema
        );
    END IF;
END $$;

SET search_path TO adianti;

INSERT INTO system_program (id, name, controller)
SELECT (SELECT coalesce(max(id),0)+1 FROM system_program b), 'App Bot List', 'AppBotList'
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema='adianti' AND table_name='system_program')
  AND NOT EXISTS (SELECT 1 FROM system_program WHERE controller = 'AppBotList');

INSERT INTO system_program (id, name, controller)
SELECT (SELECT coalesce(max(id),0)+1 FROM system_program b), 'App Bot Form', 'AppBotForm'
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema='adianti' AND table_name='system_program')
  AND NOT EXISTS (SELECT 1 FROM system_program WHERE controller = 'AppBotForm');

INSERT INTO system_program (id, name, controller)
SELECT (SELECT coalesce(max(id),0)+1 FROM system_program b), 'App Bot FAQ List', 'AppBotFaqList'
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema='adianti' AND table_name='system_program')
  AND NOT EXISTS (SELECT 1 FROM system_program WHERE controller = 'AppBotFaqList');

INSERT INTO system_program (id, name, controller)
SELECT (SELECT coalesce(max(id),0)+1 FROM system_program b), 'App Bot FAQ Form', 'AppBotFaqForm'
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema='adianti' AND table_name='system_program')
  AND NOT EXISTS (SELECT 1 FROM system_program WHERE controller = 'AppBotFaqForm');

INSERT INTO system_program (id, name, controller)
SELECT (SELECT coalesce(max(id),0)+1 FROM system_program b), 'App Bot Connect Form', 'AppBotConnectForm'
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema='adianti' AND table_name='system_program')
  AND NOT EXISTS (SELECT 1 FROM system_program WHERE controller = 'AppBotConnectForm');

DO $$
DECLARE
    v_program_id INT;
BEGIN
    FOR v_program_id IN
        SELECT p.id
        FROM system_program p
        WHERE p.controller IN ('AppBotList', 'AppBotForm', 'AppBotFaqList', 'AppBotFaqForm', 'AppBotConnectForm')
    LOOP
        IF NOT EXISTS (
            SELECT 1
            FROM system_group_program gp
            WHERE gp.system_group_id = 1
              AND gp.system_program_id = v_program_id
        ) THEN
            INSERT INTO system_group_program (id, system_group_id, system_program_id)
            VALUES ( (SELECT coalesce(max(id),0)+1 FROM system_group_program b), 1, v_program_id );
        END IF;
    END LOOP;
END $$;
