-- Migration idempotente para configuracoes de agente e acoes por bot

SET search_path TO app;

ALTER TABLE app.app_bot
    ADD COLUMN IF NOT EXISTS top_p NUMERIC(4,3) NOT NULL DEFAULT 1.000,
    ADD COLUMN IF NOT EXISTS max_tokens INT NOT NULL DEFAULT 1000,
    ADD COLUMN IF NOT EXISTS max_messages_context INT NOT NULL DEFAULT 50,
    ADD COLUMN IF NOT EXISTS split_long_replies CHAR(1) NOT NULL DEFAULT 'Y' CHECK (split_long_replies IN ('Y','N')),
    ADD COLUMN IF NOT EXISTS avoid_repetition CHAR(1) NOT NULL DEFAULT 'N' CHECK (avoid_repetition IN ('Y','N')),
    ADD COLUMN IF NOT EXISTS wait_seconds INT NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS human_handoff_pause_minutes INT NOT NULL DEFAULT 5,
    ADD COLUMN IF NOT EXISTS allow_audio CHAR(1) NOT NULL DEFAULT 'Y' CHECK (allow_audio IN ('Y','N')),
    ADD COLUMN IF NOT EXISTS allow_image CHAR(1) NOT NULL DEFAULT 'Y' CHECK (allow_image IN ('Y','N')),
    ADD COLUMN IF NOT EXISTS allow_pdf CHAR(1) NOT NULL DEFAULT 'Y' CHECK (allow_pdf IN ('Y','N')),
    ADD COLUMN IF NOT EXISTS allow_code_interpreter CHAR(1) NOT NULL DEFAULT 'N' CHECK (allow_code_interpreter IN ('Y','N')),
    ADD COLUMN IF NOT EXISTS allow_web_search CHAR(1) NOT NULL DEFAULT 'N' CHECK (allow_web_search IN ('Y','N'));

CREATE TABLE IF NOT EXISTS app_bot_action (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    app_bot_id UUID NOT NULL REFERENCES app_bot(id) ON DELETE CASCADE,
    nome VARCHAR(120) NOT NULL,
    tipo VARCHAR(40) NOT NULL CHECK (tipo IN ('resposta_fixa', 'handoff_humano', 'webhook')),
    gatilho VARCHAR(180) NOT NULL,
    resposta_fixa TEXT,
    config_json JSONB NOT NULL DEFAULT '{}'::jsonb,
    ordem INT NOT NULL DEFAULT 0,
    ativo CHAR(1) NOT NULL DEFAULT 'Y' CHECK (ativo IN ('Y','N')),
    criado_em TIMESTAMP NOT NULL DEFAULT NOW(),
    atualizado_em TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS app_bot_action_bot_idx ON app_bot_action (app_bot_id);
CREATE INDEX IF NOT EXISTS app_bot_action_ordem_idx ON app_bot_action (app_bot_id, ordem);
CREATE INDEX IF NOT EXISTS app_bot_action_tipo_idx ON app_bot_action (tipo);

SET search_path TO adianti;

INSERT INTO system_program (id, name, controller)
SELECT (SELECT coalesce(max(id),0)+1 FROM system_program b), 'App Bot Action List', 'AppBotActionList'
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema='adianti' AND table_name='system_program')
  AND NOT EXISTS (SELECT 1 FROM system_program WHERE controller = 'AppBotActionList');

INSERT INTO system_program (id, name, controller)
SELECT (SELECT coalesce(max(id),0)+1 FROM system_program b), 'App Bot Action Form', 'AppBotActionForm'
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema='adianti' AND table_name='system_program')
  AND NOT EXISTS (SELECT 1 FROM system_program WHERE controller = 'AppBotActionForm');

DO $$
DECLARE
    v_program_id INT;
BEGIN
    FOR v_program_id IN
        SELECT p.id
        FROM system_program p
        WHERE p.controller IN ('AppBotActionList', 'AppBotActionForm')
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
