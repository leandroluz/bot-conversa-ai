SET search_path TO app;

CREATE TABLE IF NOT EXISTS app_contato (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    nome VARCHAR(150) NOT NULL,
    telefone VARCHAR(30) NOT NULL,
    foto_url TEXT,
    criado_em TIMESTAMP DEFAULT NOW()
);

CREATE UNIQUE INDEX IF NOT EXISTS app_contato_telefone_idx ON app_contato (telefone);
ADD CONSTRAINT app_contato_telefone_uk UNIQUE (telefone);

CREATE TABLE IF NOT EXISTS app_session (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    contato_id UUID REFERENCES app_contato(id) ON DELETE SET NULL,
    canal VARCHAR(50) NOT NULL DEFAULT 'n8n',
    identificador_externo VARCHAR(100), -- ex: telefone, whatsapp_id, etc.
    criado_em TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS app_mensagem (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    session_id UUID NOT NULL REFERENCES app_session(id) ON DELETE CASCADE,
    contato_id UUID REFERENCES app_contato(id) ON DELETE SET NULL,
    remetente VARCHAR(20) NOT NULL CHECK (remetente IN ('usuario', 'assistente')),
    mensagem TEXT NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS app_mensagem_contato_idx ON app_mensagem (contato_id);
