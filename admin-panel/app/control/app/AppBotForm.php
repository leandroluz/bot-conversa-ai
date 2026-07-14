<?php
/**
 * AppBotForm
 *
 * @package    control
 * @subpackage app
 */
class AppBotForm extends TStandardForm
{
    protected $form;
    protected $defaultEvolutionUrl;
    protected $defaultEvolutionApiKey;
    protected $defaultEvolutionInstanceId = 'Pendente (gerado automaticamente)';
    protected $defaultTelegramWebhookUrl;

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();

        parent::setTargetContainer('adianti_right_panel');

        $this->setDatabase('permission');
        $this->setActiveRecord('AppBot');
        $this->setAfterSaveAction(new TAction(['AppBotList', 'onReload']));
        $this->defaultEvolutionUrl = $this->getDefaultEvolutionUrl();
        $this->defaultEvolutionApiKey = $this->getDefaultEvolutionApiKey();
        $this->defaultTelegramWebhookUrl = $this->getDefaultTelegramWebhookUrl();

        $this->form = new BootstrapFormBuilder('form_AppBot');
        $this->form->setFormTitle('Bot de Atendimento');
        $this->form->enableClientValidation();

        $id = new TEntry('id');
        $system_unit_id = new TDBCombo('system_unit_id', 'permission', 'SystemUnit', 'id', 'name', 'name asc');
        $nome = new TEntry('nome');
        $canal = new TCombo('canal[]');
        $evolution_instance = new TEntry('evolution_instance');
        $evolution_instance_id = new TEntry('evolution_instance_id');
        $evolution_api_url = new TEntry('evolution_api_url');
        $evolution_api_key = new TEntry('evolution_api_key');
        $telegram_bot_token = new TEntry('telegram_bot_token');
        $telegram_bot_username = new TEntry('telegram_bot_username');
        $telegram_webhook_secret = new TEntry('telegram_webhook_secret');
        $telegram_webhook_url = new TEntry('telegram_webhook_url');
        $modelo_llm = new TCombo('modelo_llm');
        $temperatura = new TEntry('temperatura');
        $top_p = new TEntry('top_p');
        $max_tokens = new TEntry('max_tokens');
        $max_messages_context = new TEntry('max_messages_context');
        $split_long_replies = new TCombo('split_long_replies');
        $avoid_repetition = new TCombo('avoid_repetition');
        $wait_seconds = new TEntry('wait_seconds');
        $human_handoff_pause_minutes = new TEntry('human_handoff_pause_minutes');
        $allow_audio = new TCombo('allow_audio');
        $allow_image = new TCombo('allow_image');
        $allow_pdf = new TCombo('allow_pdf');
        $allow_code_interpreter = new TCombo('allow_code_interpreter');
        $allow_web_search = new TCombo('allow_web_search');
        $faq_top_k = new TEntry('faq_top_k');
        $similaridade_minima = new TEntry('similaridade_minima');
        $usar_rag = new TCombo('usar_rag');
        $ativo = new TCombo('ativo');
        $instrucoes = new TText('instrucoes');

        $canal->addItems([
            'whatsapp' => 'WhatsApp (Evolution)',
            'telegram' => 'Telegram',
        ]);
        $canal->setProperty('multiple', 'multiple');
        $canal->enableSearch();
        $usar_rag->addItems(['Y' => 'Sim', 'N' => 'Não']);
        $ativo->addItems(['Y' => 'Sim', 'N' => 'Não']);
        $split_long_replies->addItems(['Y' => 'Sim', 'N' => 'Não']);
        $avoid_repetition->addItems(['Y' => 'Sim', 'N' => 'Não']);
        $allow_audio->addItems(['Y' => 'Sim', 'N' => 'Não']);
        $allow_image->addItems(['Y' => 'Sim', 'N' => 'Não']);
        $allow_pdf->addItems(['Y' => 'Sim', 'N' => 'Não']);
        $allow_code_interpreter->addItems(['Y' => 'Sim', 'N' => 'Não']);
        $allow_web_search->addItems(['Y' => 'Sim', 'N' => 'Não']);
        $modelo_llm->addItems($this->loadOllamaModelOptions());
        $modelo_llm->enableSearch();

        $this->form->appendPage('Geral');
        $this->form->addFields([new TLabel('Id')], [$id]);
        $this->form->addFields([new TLabel('Unidade')], [$system_unit_id], [new TLabel('Nome')], [$nome]);
        $this->form->addFields([new TLabel('Ativo')], [$ativo]);

        $this->form->appendPage('Canais');
        $this->form->addFields([new TLabel('Canal')], [$canal]);
        $this->form->addFields([new TFormSeparator('WhatsApp (Evolution)')]);
        $this->form->addFields([new TLabel('Instância Evolution')], [$evolution_instance], [new TLabel('Instance ID Evolution')], [$evolution_instance_id]);
        $this->form->addFields([new TLabel('URL Evolution API')], [$evolution_api_url]);
        $this->form->addFields([new TLabel('Chave Evolution API')], [$evolution_api_key]);
        $this->form->addFields([new TFormSeparator('Telegram')]);
        $this->form->addFields([new TLabel('Token do Bot Telegram')], [$telegram_bot_token]);
        $this->form->addFields([new TLabel('Usuário do Bot Telegram')], [$telegram_bot_username], [new TLabel('Webhook Secret Telegram')], [$telegram_webhook_secret]);
        $this->form->addFields([new TLabel('Webhook URL Telegram')], [$telegram_webhook_url]);

        $this->form->appendPage('LLM e Conhecimento');
        $this->form->addFields([new TLabel('Modelo LLM')], [$modelo_llm], [new TLabel('Temperatura')], [$temperatura], [new TLabel('Top P')], [$top_p]);
        $this->form->addFields([new TLabel('Máximo de tokens')], [$max_tokens], [new TLabel('Qtd. mensagens contexto')], [$max_messages_context]);
        $this->form->addFields([new TLabel('Usar RAG')], [$usar_rag], [new TLabel('Top K FAQ')], [$faq_top_k], [new TLabel('Similaridade mínima')], [$similaridade_minima]);
        $this->form->addFields([new TLabel('Instruções (Prompt)')], [$instrucoes]);

        $this->form->appendPage('Avançado');
        $this->form->addFields([new TLabel('Dividir respostas longas')], [$split_long_replies], [new TLabel('Evitar repetição')], [$avoid_repetition]);
        $this->form->addFields([new TLabel('Tempo de espera (segundos)')], [$wait_seconds], [new TLabel('Pausa por intervenção (min)')], [$human_handoff_pause_minutes]);
        $this->form->addFields([new TLabel('Interpretar áudio')], [$allow_audio], [new TLabel('Interpretar imagem')], [$allow_image], [new TLabel('Interpretar PDF')], [$allow_pdf]);
        $this->form->addFields([new TLabel('Code Interpreter')], [$allow_code_interpreter], [new TLabel('Web Search')], [$allow_web_search]);

        $id->setEditable(false);
        $evolution_instance_id->setEditable(false);
        $evolution_api_url->setEditable(false);
        $evolution_api_key->setEditable(false);
        $telegram_bot_token->setProperty('type', 'password');
        $evolution_instance_id->setValue($this->defaultEvolutionInstanceId);
        $evolution_api_url->setValue($this->defaultEvolutionUrl);
        $evolution_api_key->setValue($this->defaultEvolutionApiKey);
        $telegram_webhook_url->setValue($this->defaultTelegramWebhookUrl);

        $id->setSize('30%');
        $system_unit_id->setSize('100%');
        $nome->setSize('100%');
        $canal->setSize('100%');
        $evolution_instance->setSize('100%');
        $evolution_instance_id->setSize('100%');
        $evolution_api_url->setSize('100%');
        $evolution_api_key->setSize('100%');
        $telegram_bot_token->setSize('100%');
        $telegram_bot_username->setSize('100%');
        $telegram_webhook_secret->setSize('100%');
        $telegram_webhook_url->setSize('100%');
        $modelo_llm->setSize('100%');
        $temperatura->setSize('100%');
        $top_p->setSize('100%');
        $max_tokens->setSize('100%');
        $max_messages_context->setSize('100%');
        $split_long_replies->setSize('100%');
        $avoid_repetition->setSize('100%');
        $wait_seconds->setSize('100%');
        $human_handoff_pause_minutes->setSize('100%');
        $allow_audio->setSize('100%');
        $allow_image->setSize('100%');
        $allow_pdf->setSize('100%');
        $allow_code_interpreter->setSize('100%');
        $allow_web_search->setSize('100%');
        $faq_top_k->setSize('100%');
        $similaridade_minima->setSize('100%');
        $usar_rag->setSize('100%');
        $ativo->setSize('100%');
        $instrucoes->setSize('100%', 180);

        $system_unit_id->addValidation('Unidade', new TRequiredValidator);
        $nome->addValidation('Nome', new TRequiredValidator);
        $canal->addValidation('Canal', new TRequiredValidator);

        $btn = $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'far:save');
        $btn->class = 'btn btn-sm btn-primary';
        $this->form->addActionLink('Limpar', new TAction([$this, 'onEdit']), 'fa:eraser red');
        $this->form->addHeaderActionLink('Atualizar modelos', new TAction([$this, 'onRefreshModels']), 'fas:sync-alt blue');

        $this->form->addHeaderActionLink('Fechar', new TAction([$this, 'onClose']), 'fa:times red');

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($this->form);

        parent::add($container);
    }

    /**
     * on close
     */
    public static function onClose($param)
    {
        TScript::create("Template.closeRightPanel()");
    }

    /**
     * Set sensible defaults for new records
     */
    public function onEdit($param)
    {
        parent::onEdit($param);

        if (empty($param['key'])) {
            $data = $this->form->getData();
            if (empty($data->canal) && isset($data->{'canal[]'})) {
                $data->canal = $data->{'canal[]'};
            }
            $data->modelo_llm = $data->modelo_llm ?? 'phi3';
            $data->temperatura = $data->temperatura ?? '0.2';
            $data->top_p = $data->top_p ?? '1.0';
            $data->canal = $data->canal ?? ['whatsapp'];
            $data->max_tokens = $data->max_tokens ?? '1000';
            $data->max_messages_context = $data->max_messages_context ?? '50';
            $data->split_long_replies = $data->split_long_replies ?? 'Y';
            $data->avoid_repetition = $data->avoid_repetition ?? 'N';
            $data->wait_seconds = $data->wait_seconds ?? '1';
            $data->human_handoff_pause_minutes = $data->human_handoff_pause_minutes ?? '5';
            $data->allow_audio = $data->allow_audio ?? 'Y';
            $data->allow_image = $data->allow_image ?? 'Y';
            $data->allow_pdf = $data->allow_pdf ?? 'Y';
            $data->allow_code_interpreter = $data->allow_code_interpreter ?? 'N';
            $data->allow_web_search = $data->allow_web_search ?? 'N';
            $data->faq_top_k = $data->faq_top_k ?? '5';
            $data->similaridade_minima = $data->similaridade_minima ?? '0.75';
            $data->usar_rag = $data->usar_rag ?? 'Y';
            $data->ativo = $data->ativo ?? 'Y';
            $data->evolution_instance_id = $this->defaultEvolutionInstanceId;
            $data->evolution_api_url = $this->defaultEvolutionUrl;
            $data->evolution_api_key = $this->defaultEvolutionApiKey;
            $data->telegram_webhook_url = $this->defaultTelegramWebhookUrl;

            if (empty($data->system_unit_id) && TSession::getValue('userunitid')) {
                $data->system_unit_id = TSession::getValue('userunitid');
            }

            $this->form->setData($data);
        } else {
            $data = $this->form->getData();
            if (empty($data->canal) && isset($data->{'canal[]'})) {
                $data->canal = $data->{'canal[]'};
            }
            if (empty($data->id) && !empty($param['key'])) {
                try {
                    TTransaction::open('permission');
                    $bot = new AppBot($param['key']);
                    TTransaction::close();
                    $data = clone $bot;
                } catch (Exception $e) {
                    if (TTransaction::get()) {
                        TTransaction::rollback();
                    }
                }
            }

            if (!empty($data->id)) {
                $channels = AppBot::normalizeChannels($data->canal ?? 'whatsapp');
                $data->canal = !empty($channels) ? $channels : ['whatsapp'];
                $this->form->setData($data);
            }
        }
    }

    /**
     * Validate and save bot data
     */
    public function onSave()
    {
        try {
            $this->form->validate();
            $data = $this->form->getData();
            if (empty($data->canal) && isset($data->{'canal[]'})) {
                $data->canal = $data->{'canal[]'};
            }

            $data->modelo_llm = $data->modelo_llm ?? 'phi3';
            $data->temperatura = $data->temperatura ?? '0.2';
            $data->top_p = $data->top_p ?? '1.0';
            $data->canal = $data->canal ?? ['whatsapp'];
            $data->max_tokens = $data->max_tokens ?? '1000';
            $data->max_messages_context = $data->max_messages_context ?? '50';
            $data->split_long_replies = $data->split_long_replies ?? 'Y';
            $data->avoid_repetition = $data->avoid_repetition ?? 'N';
            $data->wait_seconds = $data->wait_seconds ?? '1';
            $data->human_handoff_pause_minutes = $data->human_handoff_pause_minutes ?? '5';
            $data->allow_audio = $data->allow_audio ?? 'Y';
            $data->allow_image = $data->allow_image ?? 'Y';
            $data->allow_pdf = $data->allow_pdf ?? 'Y';
            $data->allow_code_interpreter = $data->allow_code_interpreter ?? 'N';
            $data->allow_web_search = $data->allow_web_search ?? 'N';
            $data->faq_top_k = $data->faq_top_k ?? '5';
            $data->similaridade_minima = $data->similaridade_minima ?? '0.75';
            $data->usar_rag = $data->usar_rag ?? 'Y';
            $data->ativo = $data->ativo ?? 'Y';

            $channels = AppBot::normalizeChannels($data->canal);
            if (empty($channels)) {
                throw new Exception('Selecione ao menos um canal válido (WhatsApp ou Telegram).');
            }
            $data->canal = AppBot::serializeChannels($channels);

            if (in_array('whatsapp', $channels, true)) {
                if (empty(trim((string) $data->evolution_instance))) {
                    throw new Exception('Instância Evolution é obrigatória para canal WhatsApp.');
                }

                $data->evolution_api_url = !empty($data->evolution_api_url)
                    ? $this->normalizeEvolutionUrl((string) $data->evolution_api_url)
                    : $this->defaultEvolutionUrl;
                $data->evolution_api_key = !empty($data->evolution_api_key)
                    ? $data->evolution_api_key
                    : $this->defaultEvolutionApiKey;
            }

            if (in_array('telegram', $channels, true)) {
                if (empty(trim((string) $data->telegram_bot_token))) {
                    throw new Exception('Token do Bot Telegram é obrigatório para canal Telegram.');
                }
            }

            if (trim((string) $data->evolution_instance_id) === $this->defaultEvolutionInstanceId) {
                $data->evolution_instance_id = null;
            }

            TTransaction::open('permission');

            $object = !empty($data->id) ? new AppBot($data->id) : new AppBot;
            $raw = (array) $data;
            unset($raw['canal[]']);
            $raw['canal'] = $data->canal;
            $object->fromArray($raw);
            $object->store();

            $this->form->setData($object);
            TTransaction::close();

            new TMessage('info', 'Registro salvo', $this->afterSaveAction);
        } catch (Exception $e) {
            if (TTransaction::get()) {
                TTransaction::rollback();
            }
            $this->form->setData($this->form->getData());
            new TMessage('error', $e->getMessage());
        }
    }

    /**
     * Normalize and validate Evolution API URL
     */
    private function normalizeEvolutionUrl($url)
    {
        if (!preg_match('#^https?://#i', $url)) {
            throw new Exception('URL Evolution API inválida. Use http:// ou https://');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('URL Evolution API inválida.');
        }

        $parts = parse_url($url);
        if (empty($parts['host'])) {
            throw new Exception('URL Evolution API inválida: host não informado.');
        }

        if (!in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)) {
            throw new Exception('URL Evolution API inválida: protocolo deve ser http ou https.');
        }

        return rtrim($url, '/');
    }

    /**
     * Returns default Evolution URL for this environment
     */
    private function getDefaultEvolutionUrl()
    {
        return rtrim((string) (getenv('EVOLUTION_API_BASE_URL') ?: 'http://evolution-api:8080'), '/');
    }

    /**
     * Returns default Evolution API key for this environment
     */
    private function getDefaultEvolutionApiKey()
    {
        return (string) (getenv('EVOLUTION_API_KEY') ?: 'change-me');
    }

    /**
     * Returns default Telegram webhook URL for this environment
     */
    private function getDefaultTelegramWebhookUrl()
    {
        $explicit = trim((string) getenv('TELEGRAM_DEFAULT_WEBHOOK_URL'));
        if ($explicit !== '') {
            return $explicit;
        }

        $base = trim((string) (getenv('N8N_WEBHOOK_URL') ?: getenv('N8N_EDITOR_BASE_URL') ?: 'http://localhost:5678'));
        return rtrim($base, '/') . '/webhook/telegram-rag-bot-modular/<TELEGRAM_BOT_TOKEN>';
    }

    /**
     * Refresh LLM model options from Ollama API
     */
    public function onRefreshModels($param)
    {
        try {
            $models = $this->loadOllamaModelOptions();
            TCombo::reload('form_AppBot', 'modelo_llm', $models, true);

            $data = $this->form->getData();
            TForm::sendData('form_AppBot', $data, false, false);
            new TMessage('info', 'Modelos atualizados com sucesso.');
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    /**
     * Load available models from Ollama API with fallback values
     */
    private function loadOllamaModelOptions()
    {
        $models = [];
        $base = rtrim((string) (getenv('OLLAMA_BASE_URL') ?: 'http://ollama:11434'), '/');

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $base . '/api/tags',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 4,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);

            $raw = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if (!$error && $status >= 200 && $status < 300 && !empty($raw)) {
                $payload = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && !empty($payload['models']) && is_array($payload['models'])) {
                    foreach ($payload['models'] as $item) {
                        $name = trim((string) ($item['name'] ?? ''));
                        if ($name !== '') {
                            $models[$name] = $name;
                        }
                    }
                }
            }
        } catch (Exception $e) {
        }

        if (empty($models)) {
            $models = [
                'phi3' => 'phi3',
                'llama3.2' => 'llama3.2',
                'mistral' => 'mistral',
            ];
        }

        if (!isset($models['phi3'])) {
            $models = ['phi3' => 'phi3'] + $models;
        }

        return $models;
    }
}
