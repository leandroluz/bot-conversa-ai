# Evolution Simple Atendimento

Arquivo de workflow: `n8n/workflows/evolution-simple-atendimento.json`

## O que ele faz

1. Recebe webhook da Evolution API.
2. Normaliza a mensagem recebida.
3. Ignora mensagens de grupo, sem texto ou enviadas pelo proprio bot.
4. Carrega no PostgreSQL o bot vinculado a `evolution_instance`.
5. Busca acoes e FAQs cadastradas para esse bot.
6. Escolhe a melhor resposta por palavras-chave.
7. Envia a resposta para o WhatsApp via Evolution API.
8. Salva pergunta e resposta em `app.app_mensagem`.

## Fluxo de resposta

- Se encontrar uma acao por gatilho, usa a `resposta_fixa`.
- Se encontrar uma FAQ compatível, responde com a FAQ.
- Se nao encontrar nada, mostra um menu curto com os principais assuntos.

## Seed padrao

O projeto agora inclui `admin-panel/app/database/110_seed_simple_bot.sql`, que cria:

- Bot: `Bot Atendimento Teste`
- Instancia Evolution: `teste-atendimento`
- Acoes: boas-vindas e encaminhamento humano
- FAQs: horario, endereco, documentos e atendente

## Importacao no n8n

1. `Workflows` -> `Import from file`
2. Selecione `n8n/workflows/evolution-simple-atendimento.json`
3. Ajuste as credenciais do node `Postgres`, se necessario
4. Ative o workflow

## Endpoint do webhook

Path configurado no workflow: `evolution-simple-atendimento`

## Auto-teste no próprio WhatsApp (sem outro número)

Para testar no seu próprio número, o workflow aceita mensagens `fromMe` somente no modo de auto-teste:

- Configure no container do n8n:
  - `WA_SELF_TEST_PHONE`: seu número com DDI (somente dígitos), ex.: `5511999999999`
  - `WA_SELF_TEST_PREFIX`: prefixo de teste (opcional, padrão: `/bot `)
- Envie mensagem para você mesmo iniciando com o prefixo, por exemplo:
  - `/bot qual horario de visita?`

Com isso, o fluxo evita loop porque respostas automáticas do bot (sem prefixo) não são reprocessadas.
