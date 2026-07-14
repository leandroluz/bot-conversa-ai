# Evolution AI Agent

Arquivo de workflow: `n8n/workflows/evolution-ai-agent.json`

> **Atenção:** este workflow é mantido como referência e ainda contém um nó
> `Ollama Chat Model`. Ollama não faz parte da infraestrutura atual. Substitua o
> nó pelo provedor de IA escolhido antes de importar ou ativar o fluxo.

## O que ele faz

1. **Recepção de Webhook**: Recebe o payload da Evolution API no endpoint `/webhook/evolution-ai-agent`.
2. **Normalização**: Limpa e extrai os dados essenciais (telefone, mensagem de texto, instância e sinalizações). Ignora mensagens enviadas pelo próprio bot (se não for auto-teste), grupos ou mensagens sem texto.
3. **Resposta Imediata**: Responde ao webhook com HTTP 200/OK imediatamente para evitar timeouts na Evolution API.
4. **Registro do Usuário**: Salva a mensagem recebida do usuário no PostgreSQL (`app.app_mensagem`).
5. **Execução do Agente de IA**:
   - Processa a mensagem utilizando o nó **AI Agent** do n8n.
   - O arquivo de referência utiliza **Ollama Chat Model** e requer a troca por
     um modelo compatível com o ambiente de destino.
   - Mantém o histórico de conversas individualizado por número de telefone usando o nó **Window Buffer Memory** (chave de sessão baseada no telefone do usuário).
6. **Envio da Resposta**: Envia a resposta final para o WhatsApp do usuário usando a rota `/message/sendText` da Evolution API.
7. **Registro do Assistente**: Salva a resposta gerada pela IA no PostgreSQL (`app.app_mensagem`) para auditoria e exibição no painel administrativo.

## Como Importar no n8n

1. Acesse o painel do seu n8n (`http://localhost:5678`).
2. Clique em **Workflows** -> **Import from file...**.
3. Selecione o arquivo [evolution-ai-agent.json](file:///home/leandroluz/Documentos/projetos/bot-conversa-ai/n8n/workflows/evolution-ai-agent.json).
4. Verifique as credenciais do node **Postgres** (ajuste se necessário).
5. Ative o workflow (**Active** no canto superior direito).

## Configuração do Webhook na Evolution API

Aponte o webhook da sua instância da Evolution API (ou o Webhook Global) para o endpoint gerado pelo n8n:

```text
http://n8n:5678/webhook/evolution-ai-agent
```

## Auto-teste no próprio WhatsApp (sem outro número)

O workflow suporta a execução de auto-teste. Para utilizá-lo:
- Ao enviar mensagens para você mesmo iniciadas com `/bot ` (ou em qualquer conversa onde você enviar uma mensagem iniciando com `/bot `), o fluxo tratará a mensagem e responderá automaticamente.
- Isso é feito identificando que a mensagem partiu de você (`fromMe` é verdadeiro) e possui o prefixo `/bot `, dispensando qualquer configuração de variável de ambiente no container do n8n.
