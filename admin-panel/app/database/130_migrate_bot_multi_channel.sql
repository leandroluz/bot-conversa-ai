-- Migration idempotente para permitir multiplos canais no campo app_bot.canal

SET search_path TO app;

UPDATE app.app_bot
   SET canal = lower(regexp_replace(coalesce(canal, ''), '\s+', '', 'g'));

UPDATE app.app_bot
   SET canal = 'whatsapp'
 WHERE canal IS NULL OR trim(canal) = '';

UPDATE app.app_bot
   SET canal = 'whatsapp,telegram'
 WHERE canal LIKE '%whatsapp%'
   AND canal LIKE '%telegram%';

ALTER TABLE app.app_bot DROP CONSTRAINT IF EXISTS app_bot_canal_chk;

ALTER TABLE app.app_bot
    ADD CONSTRAINT app_bot_canal_chk
    CHECK (canal IN ('whatsapp', 'telegram', 'whatsapp,telegram'));
