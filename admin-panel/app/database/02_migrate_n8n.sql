-- One-time migration to move existing n8n objects from public to n8n schema
-- Run manually with psql after stopping n8n.

DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'n8n') THEN
    CREATE ROLE n8n LOGIN PASSWORD 'n8n123';
  END IF;
END $$;

CREATE SCHEMA IF NOT EXISTS n8n AUTHORIZATION n8n;
GRANT CONNECT ON DATABASE atendente TO n8n;
GRANT USAGE, CREATE ON SCHEMA n8n TO n8n;
ALTER ROLE n8n SET search_path = n8n, public;

-- Move tables from public to n8n
DO $$
DECLARE
  r record;
BEGIN
  FOR r IN
    SELECT tablename
    FROM pg_tables
    WHERE schemaname = 'public'
  LOOP
    EXECUTE format('ALTER TABLE public.%I SET SCHEMA n8n', r.tablename);
  END LOOP;
END $$;

-- Move sequences from public to n8n
DO $$
DECLARE
  r record;
BEGIN
  FOR r IN
    SELECT sequence_name
    FROM information_schema.sequences
    WHERE sequence_schema = 'public'
  LOOP
    EXECUTE format('ALTER SEQUENCE public.%I SET SCHEMA n8n', r.sequence_name);
  END LOOP;
END $$;

-- Move views from public to n8n
DO $$
DECLARE
  r record;
BEGIN
  FOR r IN
    SELECT table_name
    FROM information_schema.views
    WHERE table_schema = 'public'
  LOOP
    EXECUTE format('ALTER VIEW public.%I SET SCHEMA n8n', r.table_name);
  END LOOP;
END $$;

-- Move types (enums) from public to n8n
DO $$
DECLARE
  r record;
BEGIN
  FOR r IN
    SELECT t.typname
    FROM pg_type t
    JOIN pg_namespace n ON n.oid = t.typnamespace
    WHERE n.nspname = 'public'
      AND t.typtype = 'e'
  LOOP
    EXECUTE format('ALTER TYPE public.%I SET SCHEMA n8n', r.typname);
  END LOOP;
END $$;

-- Reassign ownership to n8n for moved objects
DO $$
DECLARE
  r record;
BEGIN
  FOR r IN
    SELECT tablename FROM pg_tables WHERE schemaname = 'n8n'
  LOOP
    EXECUTE format('ALTER TABLE n8n.%I OWNER TO n8n', r.tablename);
  END LOOP;

  FOR r IN
    SELECT sequence_name FROM information_schema.sequences WHERE sequence_schema = 'n8n'
  LOOP
    EXECUTE format('ALTER SEQUENCE n8n.%I OWNER TO n8n', r.sequence_name);
  END LOOP;

  FOR r IN
    SELECT table_name FROM information_schema.views WHERE table_schema = 'n8n'
  LOOP
    EXECUTE format('ALTER VIEW n8n.%I OWNER TO n8n', r.table_name);
  END LOOP;
END $$;
