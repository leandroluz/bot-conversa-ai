# bot-conversa-ai 🤖💬

O **bot-conversa-ai** é uma plataforma local de atendimento com Inteligência Artificial,
voltada para conversas institucionais via múltiplos canais (como WhatsApp e Telegram),
utilizando **LLMs open-source rodando localmente**, com foco em controle, segurança e rastreabilidade.

O projeto foi concebido para ambientes que exigem **autonomia tecnológica**, **auditoria** e
**execução local**, como órgãos públicos, instituições e ambientes corporativos.

---

## 🎯 Objetivo do Projeto

- Realizar **atendimento inicial automatizado**
- Fornecer **informações institucionais básicas**
- Orientar usuários de forma clara e padronizada
- Encaminhar demandas para atendimento humano quando necessário
- Registrar conversas para fins administrativos e auditoria
- Operar **sem dependência de APIs externas**

---

## 🧠 Características Principais

- Execução **100% local / self-hosted**
- Uso de **LLMs open-source** via Ollama
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
n8n (orquestração e regras)
↓
Ollama (LLM local)
↓
Resposta ao usuário
↓
PostgreSQL (registro de conversas)
↓
Painel Administrativo



---

## 🚀 Stack Tecnológica

- Docker / Docker Compose
- Ollama (LLM local)
- Modelo LLM: **phi3**
- n8n (workflow e automação)
- PostgreSQL (persistência e auditoria)
- Open WebUI (interface opcional para testes do Ollama)
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

Isso irá iniciar:

Ollama (LLM local)

n8n (orquestração)

PostgreSQL (banco de dados)

Open WebUI (interface opcional)

Painel Administrativo (Adianti)

--- 

## 🧰 Painel Administrativo (Adianti)

O painel Adianti foi incorporado ao projeto em `admin-panel/` e usa o **mesmo PostgreSQL**
do serviço principal (banco `atendente`). Portanto, **não use** o `docker-compose.yml`
interno do `admin-panel`.

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


## 🧠 Instalação do Modelo LLM (OBRIGATÓRIO)

Após subir os containers, é necessário baixar manualmente o modelo LLM
utilizado pelo Ollama.

Execute uma única vez:

```bash
docker exec -it ollama ollama pull phi3

```

Esse comando irá:

baixar o modelo phi3

armazená-lo de forma persistente no volume do Ollama

disponibilizá-lo para uso pelo n8n, Open WebUI e API

---

## ⚠️ Importante
O projeto não baixa modelos automaticamente durante o build
para evitar imagens Docker muito grandes e demoradas.

---

## ✔️ Verificação do Modelo (Opcional)

Para confirmar que o modelo foi instalado corretamente:

```bash

docker exec -it ollama ollama list
```

Saída esperada:

NAME         SIZE
phi3:latest  2.2 GB

---
