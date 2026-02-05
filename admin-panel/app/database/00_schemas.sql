-- Schemas and roles organization
-- Adianti + app tables live under role "atendente"
CREATE SCHEMA IF NOT EXISTS adianti AUTHORIZATION atendente;
CREATE SCHEMA IF NOT EXISTS app AUTHORIZATION atendente;

-- Separate schema/user for n8n
CREATE ROLE n8n LOGIN PASSWORD 'n8n123';
CREATE SCHEMA IF NOT EXISTS n8n AUTHORIZATION n8n;

-- Permissions for n8n
GRANT CONNECT ON DATABASE atendente TO n8n;
GRANT USAGE, CREATE ON SCHEMA n8n TO n8n;

-- Default search_path per role
ALTER ROLE atendente SET search_path = adianti, app, public;
ALTER ROLE n8n SET search_path = n8n, public;
