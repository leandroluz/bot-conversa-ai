# bot-conversa-ai 🤖💬

O **bot-conversa-ai** é uma plataforma self-hosted de atendimento automatizado,
voltada para conversas institucionais via múltiplos canais (como WhatsApp e Telegram),
com foco em controle, segurança e rastreabilidade.

O projeto foi concebido para ambientes que exigem **autonomia tecnológica**, **auditoria** e
**execução local**, como órgãos públicos, instituições e ambientes corporativos.

---

## 🎯 Objetivo do Projeto

- Realizar **atendimento inicial automatizado**
- Fornecer **informações institucionais básicas**
- Orientar usuários de forma clara e padronizada
- Encaminhar demandas para atendimento humano quando necessário
- Registrar conversas para fins administrativos e auditoria
- Permitir integração modular com provedores de IA quando necessário

---

## 🧠 Características Principais

- Execução **self-hosted**
- Orquestração de fluxos com **n8n**
- Persistência de dados em **PostgreSQL**
- Interface administrativa via **PHP (Adianti Framework)**
- Arquitetura modular e escalável
- Suporte a múltiplos canais de entrada
- Postura institucional e controle de respostas

---

## 🧩 Arquitetura

Usuário
↓
Canal de Entrada (Webhook / WhatsApp / Telegram)
↓
n8n (orquestração, FAQ e integrações)
↓
Resposta ao usuário
↓
PostgreSQL (registro de conversas)
↓
Painel Administrativo



---

## 🚀 Stack Tecnológica

- Docker / Docker Compose
- n8n (workflow e automação)
- PostgreSQL (persistência e auditoria)
- Evolution API (integração com WhatsApp)
- Redis (infraestrutura da Evolution API)
- PHP + Adianti Framework (gestão administrativa)

---

## 📋 Pré-requisitos

Antes de iniciar, certifique-se de ter instalado:

- Docker
- Docker Compose (plugin ou standalone)
- Git

---

## 📥 Clonando o Repositório

```bash
git clone https://github.com/leandroluz/bot-conversa-ai.git
cd bot-conversa-ai 
```
---

## ▶️ Subindo os Containers

Execute o comando abaixo para iniciar todos os serviços:
```bash
docker compose up -d
```

Isso irá iniciar n8n, PostgreSQL, Evolution API, Redis, pgAdmin e o painel
administrativo Adianti.

--- 

## 🧰 Painel Administrativo (Adianti)

O painel Adianti foi incorporado ao projeto em `admin-panel/` e usa o **mesmo PostgreSQL**
do serviço principal (banco `atendente`). Portanto, **não use** o `docker-compose.yml`
interno do `admin-panel`.
Os Dockerfiles do Adianti ficam na raiz do projeto (`Dockerfile.adianti` e `Dockerfile.adianti-db`).
O `docker-compose.adianti.yml` original foi preservado apenas como referência.
O `Dockerfile.adianti-db` foi mantido apenas como referência e não é usado neste compose.

### 🌐 Acesso
Após subir os containers, acesse:

```
http://localhost:8081
```

### 🧱 Inicialização do banco do Adianti
O Adianti precisa de algumas tabelas base. Para inicializar, rode:

```bash
for f in admin-panel/app/database/*.sql; do
  docker exec -i postgres psql -U atendente -d atendente < "$f"
done
```


## Teste Rápido: Bot de Atendimento Simples

Se a ideia e validar a estrutura antes de ligar RAG/LLM, use o fluxo simples baseado em FAQ e palavras-chave.

### O que foi deixado pronto

- Seed SQL idempotente em `admin-panel/app/database/110_seed_simple_bot.sql`
- Workflow n8n em `n8n/workflows/evolution-simple-atendimento.json`
- Bot de exemplo com a instancia `teste-atendimento`

### Seed de exemplo

Em banco novo, o seed entra automaticamente porque a pasta `admin-panel/app/database/` ja esta montada no PostgreSQL.

Se o banco ja existia, rode manualmente:

```bash
docker exec -i postgres psql -U atendente -d atendente < admin-panel/app/database/110_seed_simple_bot.sql
```

### Importar workflow

No n8n (`http://localhost:5678`):

1. Importe `n8n/workflows/evolution-simple-atendimento.json`
2. Configure o node Postgres apontando para:
   - host: `postgres`
   - database: `atendente`
   - user: `atendente`
   - password: `atendente123`
3. Ative o workflow

### Como testar

1. Crie ou conecte na Evolution uma instancia chamada `teste-atendimento`
2. Configure o webhook dessa instancia para o endpoint do n8n:

```text
http://n8n:5678/webhook/evolution-simple-atendimento
```

3. Envie mensagens como:
   - `oi`
   - `qual o horario?`
   - `endereco`
   - `documentos`
   - `atendente`

As conversas ficam gravadas em `app.app_mensagem` e o bot pode ser ajustado pelo painel Adianti.

## 🧠 Bot com Agente de Inteligência Artificial

O workflow de agente é mantido como referência, mas seu nó de modelo ainda aponta
para Ollama. Como Ollama não faz mais parte da infraestrutura, substitua esse nó pelo
provedor de IA escolhido antes de importar ou ativar o workflow.

### O que foi deixado pronto

- Workflow n8n em `n8n/workflows/evolution-ai-agent.json` (documentação detalhada em `n8n/workflows/evolution-ai-agent.md`)

### Importar workflow

No n8n (`http://localhost:5678`):

1. Importe o arquivo `n8n/workflows/evolution-ai-agent.json`.
2. Configure o node **Postgres** apontando para o seu banco:
   - host: `postgres`
   - database: `atendente`
   - user: `atendente`
   - password: `atendente123`
3. Ative o workflow.

### Como testar

1. Crie ou conecte na Evolution uma instância (ex.: `teste-atendimento`).
2. Configure o webhook dessa instância para enviar eventos `MESSAGES_UPSERT` para o endpoint:

```text
http://n8n:5678/webhook/evolution-ai-agent
```

3. Após configurar um provedor compatível, envie uma pergunta para validar o fluxo.
   As mensagens também serão registradas em `app.app_mensagem`.

## Túnel ngrok para Telegram local

Para testar webhook do Telegram com n8n local, suba o serviço `ngrok` (profile `tunnel`):

```bash
cp .env.ngrok.example .env
# edite o arquivo .env e preencha NGROK_AUTHTOKEN
docker compose --profile tunnel up -d ngrok
```

Ver URL pública gerada:

```bash
curl -s http://localhost:4040/api/tunnels | jq -r '.tunnels[] | select(.proto=="https") | .public_url'
```

Exemplo de webhook para workflow Telegram:

```text
https://SEU_SUBDOMINIO.ngrok-free.app/webhook/telegram-rag-bot-modular/<TELEGRAM_BOT_TOKEN>
```
