SET search_path TO app;

DO $$
DECLARE
    v_unit_id INT;
BEGIN
    SELECT id
      INTO v_unit_id
      FROM adianti.system_unit
     WHERE id = 1
     LIMIT 1;

    IF v_unit_id IS NULL THEN
        RAISE NOTICE 'Unidade padrao nao encontrada; seed do bot simples ignorado';
        RETURN;
    END IF;

    INSERT INTO app.app_bot (
        id,
        system_unit_id,
        nome,
        evolution_instance,
        evolution_api_url,
        evolution_api_key,
        instrucoes,
        modelo_llm,
        temperatura,
        top_p,
        max_tokens,
        max_messages_context,
        split_long_replies,
        avoid_repetition,
        wait_seconds,
        human_handoff_pause_minutes,
        allow_audio,
        allow_image,
        allow_pdf,
        allow_code_interpreter,
        allow_web_search,
        usar_rag,
        faq_top_k,
        similaridade_minima,
        ativo
    ) VALUES (
        '11111111-1111-1111-1111-111111111111',
        v_unit_id,
        'Bot Atendimento Teste',
        'teste-atendimento',
        'http://evolution-api:8080',
        'change-me',
        'Voce e um bot simples de atendimento institucional. Responda de forma objetiva, educada e curta.',
        'phi3',
        0.200,
        1.000,
        1000,
        20,
        'Y',
        'N',
        1,
        5,
        'N',
        'N',
        'N',
        'N',
        'N',
        'N',
        5,
        0.7500,
        'Y'
    )
    ON CONFLICT (id) DO UPDATE
       SET system_unit_id = EXCLUDED.system_unit_id,
           nome = EXCLUDED.nome,
           evolution_instance = EXCLUDED.evolution_instance,
           evolution_api_url = EXCLUDED.evolution_api_url,
           evolution_api_key = EXCLUDED.evolution_api_key,
           instrucoes = EXCLUDED.instrucoes,
           modelo_llm = EXCLUDED.modelo_llm,
           temperatura = EXCLUDED.temperatura,
           top_p = EXCLUDED.top_p,
           max_tokens = EXCLUDED.max_tokens,
           max_messages_context = EXCLUDED.max_messages_context,
           split_long_replies = EXCLUDED.split_long_replies,
           avoid_repetition = EXCLUDED.avoid_repetition,
           wait_seconds = EXCLUDED.wait_seconds,
           human_handoff_pause_minutes = EXCLUDED.human_handoff_pause_minutes,
           allow_audio = EXCLUDED.allow_audio,
           allow_image = EXCLUDED.allow_image,
           allow_pdf = EXCLUDED.allow_pdf,
           allow_code_interpreter = EXCLUDED.allow_code_interpreter,
           allow_web_search = EXCLUDED.allow_web_search,
           usar_rag = EXCLUDED.usar_rag,
           faq_top_k = EXCLUDED.faq_top_k,
           similaridade_minima = EXCLUDED.similaridade_minima,
           ativo = EXCLUDED.ativo,
           atualizado_em = NOW();

    INSERT INTO app.app_bot_action (
        id,
        app_bot_id,
        nome,
        tipo,
        gatilho,
        resposta_fixa,
        config_json,
        ordem,
        ativo
    ) VALUES
    (
        '22222222-2222-2222-2222-222222222221',
        '11111111-1111-1111-1111-111111111111',
        'Boas-vindas',
        'resposta_fixa',
        'oi,ola,olá,bom dia,boa tarde,boa noite,menu,inicio,início,começar,comecar',
        'Ola! Sou o Bot Atendimento Teste. Posso ajudar com horario, endereco, documentos necessarios ou encaminhar para um atendente. Digite o assunto que voce precisa ou escreva atendente.',
        '{}'::jsonb,
        1,
        'Y'
    ),
    (
        '22222222-2222-2222-2222-222222222222',
        '11111111-1111-1111-1111-111111111111',
        'Encaminhamento humano',
        'handoff_humano',
        'atendente,humano,pessoa,falar com atendente,suporte',
        'Vou encaminhar seu atendimento para uma pessoa da equipe. Se puder, descreva rapidamente o que voce precisa.',
        '{}'::jsonb,
        2,
        'Y'
    )
    ON CONFLICT (id) DO UPDATE
       SET app_bot_id = EXCLUDED.app_bot_id,
           nome = EXCLUDED.nome,
           tipo = EXCLUDED.tipo,
           gatilho = EXCLUDED.gatilho,
           resposta_fixa = EXCLUDED.resposta_fixa,
           config_json = EXCLUDED.config_json,
           ordem = EXCLUDED.ordem,
           ativo = EXCLUDED.ativo,
           atualizado_em = NOW();

    INSERT INTO app.app_bot_faq (
        id,
        app_bot_id,
        pergunta,
        resposta,
        palavras_chave,
        ordem,
        ativo
    ) VALUES
    (
        '33333333-3333-3333-3333-333333333331',
        '11111111-1111-1111-1111-111111111111',
        'Qual o horario de atendimento?',
        'Nosso horario de atendimento e de segunda a sexta, das 8h as 18h.',
        'horario,hora,funcionamento,expediente,atendimento',
        1,
        'Y'
    ),
    (
        '33333333-3333-3333-3333-333333333332',
        '11111111-1111-1111-1111-111111111111',
        'Qual o endereco?',
        'Nosso atendimento presencial fica na Rua Exemplo, 123, Centro.',
        'endereco,endereço,localizacao,localização,onde fica,rua,mapa',
        2,
        'Y'
    ),
    (
        '33333333-3333-3333-3333-333333333333',
        '11111111-1111-1111-1111-111111111111',
        'Quais documentos sao necessarios?',
        'Para atendimento inicial, tenha em maos documento com foto, CPF e comprovante relacionado ao seu pedido, se houver.',
        'documentos,documento,cpf,rg,identidade,comprovante,papelada',
        3,
        'Y'
    ),
    (
        '33333333-3333-3333-3333-333333333334',
        '11111111-1111-1111-1111-111111111111',
        'Como falar com um atendente?',
        'Se voce quiser falar com um atendente, basta escrever atendente ou humano que eu registro esse pedido.',
        'atendente,humano,pessoa,ajuda,suporte',
        4,
        'Y'
    )
    ON CONFLICT (id) DO UPDATE
       SET app_bot_id = EXCLUDED.app_bot_id,
           pergunta = EXCLUDED.pergunta,
           resposta = EXCLUDED.resposta,
           palavras_chave = EXCLUDED.palavras_chave,
           ordem = EXCLUDED.ordem,
           ativo = EXCLUDED.ativo,
           atualizado_em = NOW();
END $$;
