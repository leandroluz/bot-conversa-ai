-- Migration idempotente para suportar multiplos canais por bot (WhatsApp/Telegram)

SET search_path TO app;

ALTER TABLE app.app_bot
    ADD COLUMN IF NOT EXISTS canal VARCHAR(20) NOT NULL DEFAULT 'whatsapp',
    ADD COLUMN IF NOT EXISTS telegram_bot_token TEXT,
    ADD COLUMN IF NOT EXISTS telegram_bot_username VARCHAR(120),
    ADD COLUMN IF NOT EXISTS telegram_webhook_secret VARCHAR(120),
    ADD COLUMN IF NOT EXISTS telegram_webhook_url TEXT;

UPDATE app.app_bot
   SET canal = 'whatsapp'
 WHERE canal IS NULL OR trim(canal) = '';

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
          FROM pg_constraint c
          JOIN pg_namespace n ON n.oid = c.connamespace
         WHERE c.conname = 'app_bot_canal_chk'
           AND n.nspname = 'app'
    ) THEN
        ALTER TABLE app.app_bot
            ADD CONSTRAINT app_bot_canal_chk CHECK (canal IN ('whatsapp', 'telegram'));
    END IF;
END $$;

ALTER TABLE app.app_bot
    ALTER COLUMN evolution_instance DROP NOT NULL;

CREATE INDEX IF NOT EXISTS app_bot_canal_idx ON app.app_bot (canal);
