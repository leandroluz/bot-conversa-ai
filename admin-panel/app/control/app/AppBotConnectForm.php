<?php
/**
 * AppBotConnectForm
 *
 * @package    control
 * @subpackage app
 */
class AppBotConnectForm extends TPage
{
    private $form;

    public function __construct()
    {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');
    }

    public function onLoad($param)
    {
        try {
            if (empty($param['key'])) {
                throw new Exception('Bot não informado');
            }

            TTransaction::open('permission');
            $bot = new AppBot($param['key']);
            if (empty($bot->id)) {
                throw new Exception('Bot não encontrado');
            }

            $channels = AppBot::normalizeChannels($bot->canal ?? 'whatsapp');
            if (empty($channels)) {
                $channels = ['whatsapp'];
            }
            $canal = strtolower(trim((string) ($param['canal'] ?? '')));
            if (!in_array($canal, $channels, true)) {
                $canal = $channels[0];
            }
            $snapshot = [];

            if ($canal === 'telegram') {
                $snapshot = TelegramService::getConnectionSnapshot($bot);

                $botUsername = trim((string) ($snapshot['bot_username'] ?? ''));
                if ($botUsername !== '' && $bot->telegram_bot_username !== $botUsername) {
                    $bot->telegram_bot_username = $botUsername;
                    $bot->store();
                }
            } else {
                $snapshot = EvolutionService::getConnectionSnapshot($bot);

                $instanceId = trim((string) ($snapshot['instance_id'] ?? ''));
                if ($instanceId !== '' && $bot->evolution_instance_id !== $instanceId) {
                    $bot->evolution_instance_id = $instanceId;
                    $bot->store();
                }
            }

            TTransaction::close();

            if ($canal !== 'telegram' && !empty($snapshot['exists']) && !empty($snapshot['connected']) && !empty($snapshot['manager_url'])) {
                TScript::create('window.open(' . json_encode($snapshot['manager_url']) . ', "_blank");');
            }

            if ($canal === 'telegram') {
                $this->buildTelegramView($bot, $snapshot, $channels);
            } else {
                $this->buildWhatsappView($bot, $snapshot, $channels);
            }
        } catch (Exception $e) {
            if (TTransaction::get()) {
                TTransaction::rollback();
            }
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onDisconnect($param)
    {
        try {
            if (empty($param['key'])) {
                throw new Exception('Bot não informado');
            }

            TTransaction::open('permission');
            $bot = new AppBot($param['key']);
            if (empty($bot->id)) {
                throw new Exception('Bot não encontrado');
            }
            if (!$bot->hasChannel('whatsapp')) {
                throw new Exception('Ação disponível apenas para bots WhatsApp.');
            }

            EvolutionService::disconnect($bot);
            TTransaction::close();

            new TMessage('info', 'Solicitação de desconexão enviada para a Evolution');
            AdiantiCoreApplication::loadPage(__CLASS__, 'onLoad', ['key' => $bot->id, 'canal' => 'whatsapp', 'register_state' => 'false']);
        } catch (Exception $e) {
            if (TTransaction::get()) {
                TTransaction::rollback();
            }
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onCreateInstance($param)
    {
        try {
            if (empty($param['key'])) {
                throw new Exception('Bot não informado');
            }

            TTransaction::open('permission');
            $bot = new AppBot($param['key']);
            if (empty($bot->id)) {
                throw new Exception('Bot não encontrado');
            }
            if (!$bot->hasChannel('whatsapp')) {
                throw new Exception('Ação disponível apenas para bots WhatsApp.');
            }

            EvolutionService::createInstance($bot);
            TTransaction::close();

            new TMessage('info', 'Instância criada com sucesso na Evolution');
            AdiantiCoreApplication::loadPage(__CLASS__, 'onLoad', ['key' => $bot->id, 'canal' => 'whatsapp', 'register_state' => 'false']);
        } catch (Exception $e) {
            if (TTransaction::get()) {
                TTransaction::rollback();
            }
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onOpenManager($param)
    {
        if (empty($param['url'])) {
            new TMessage('error', 'URL do manager indisponível');
            return;
        }

        TScript::create('window.open(' . json_encode($param['url']) . ', "_blank");');
    }

    public static function onClose($param)
    {
        TScript::create('Template.closeRightPanel()');
    }

    public static function onSetTelegramWebhook($param)
    {
        try {
            if (empty($param['key'])) {
                throw new Exception('Bot não informado');
            }

            TTransaction::open('permission');
            $bot = new AppBot($param['key']);
            if (empty($bot->id)) {
                throw new Exception('Bot não encontrado');
            }
            if (!$bot->hasChannel('telegram')) {
                throw new Exception('Ação disponível apenas para bots Telegram.');
            }

            $webhookUrl = trim((string) ($param['telegram_webhook_url'] ?? ''));
            $secret = trim((string) ($param['telegram_webhook_secret'] ?? ''));

            if ($webhookUrl !== '') {
                $bot->telegram_webhook_url = $webhookUrl;
            }
            if ($secret !== '') {
                $bot->telegram_webhook_secret = $secret;
            }

            if (trim((string) $bot->telegram_webhook_url) === '') {
                throw new Exception('Informe a URL de webhook do Telegram no cadastro ou nesta tela.');
            }

            TelegramService::setWebhook($bot, $bot->telegram_webhook_url, $bot->telegram_webhook_secret);
            $bot->store();

            TTransaction::close();

            new TMessage('info', 'Webhook do Telegram configurado com sucesso.');
            AdiantiCoreApplication::loadPage(__CLASS__, 'onLoad', ['key' => $bot->id, 'canal' => 'telegram', 'register_state' => 'false']);
        } catch (Exception $e) {
            if (TTransaction::get()) {
                TTransaction::rollback();
            }
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onDeleteTelegramWebhook($param)
    {
        try {
            if (empty($param['key'])) {
                throw new Exception('Bot não informado');
            }

            TTransaction::open('permission');
            $bot = new AppBot($param['key']);
            if (empty($bot->id)) {
                throw new Exception('Bot não encontrado');
            }
            if (!$bot->hasChannel('telegram')) {
                throw new Exception('Ação disponível apenas para bots Telegram.');
            }

            TelegramService::deleteWebhook($bot);
            TTransaction::close();

            new TMessage('info', 'Webhook do Telegram removido.');
            AdiantiCoreApplication::loadPage(__CLASS__, 'onLoad', ['key' => $bot->id, 'canal' => 'telegram', 'register_state' => 'false']);
        } catch (Exception $e) {
            if (TTransaction::get()) {
                TTransaction::rollback();
            }
            new TMessage('error', $e->getMessage());
        }
    }

    private function buildWhatsappView(AppBot $bot, array $snapshot, array $channels)
    {
        $this->clearChildren();

        $this->form = new BootstrapFormBuilder('form_AppBotConnect');
        $this->form->setFormTitle('Conexão WhatsApp (Evolution)');

        $bot_nome = new TEntry('bot_nome');
        $instance = new TEntry('instance');
        $instance_id = new TEntry('instance_id');
        $status = new TEntry('status');

        $bot_nome->setValue($bot->nome);
        $instance->setValue($bot->evolution_instance);
        $instance_id->setValue($snapshot['instance_id'] ?? $bot->evolution_instance_id ?? '');
        $status->setValue($snapshot['state'] ?? 'UNKNOWN');

        $bot_nome->setEditable(false);
        $instance->setEditable(false);
        $instance_id->setEditable(false);
        $status->setEditable(false);

        $bot_nome->setSize('100%');
        $instance->setSize('100%');
        $instance_id->setSize('100%');
        $status->setSize('100%');

        $this->form->addFields([new TLabel('Bot')], [$bot_nome]);
        $this->form->addFields([new TLabel('Instância Evolution')], [$instance], [new TLabel('Instance ID Evolution')], [$instance_id]);
        $this->form->addFields([new TLabel('Status')], [$status]);

        if (empty($snapshot['exists'])) {
            $warning = $snapshot['warning'] ?? '';
            $this->form->addContent([
                new TLabel('Instância não criada'),
                'Essa instância ainda não existe na Evolution. Clique em "Criar Instância".'
            ]);
            if (!empty($warning)) {
                $this->form->addContent([new TLabel('Aviso'), $warning]);
            }
        } elseif (!empty($snapshot['connected'])) {
            $this->form->addContent([new TLabel('Conectado'), 'A instância já está conectada.']);
        } else {
            $qrWidget = $this->buildQrWidget($snapshot['qr_payload'] ?? null);
            $this->form->addContent([new TLabel('QR Code para conexão')]);
            $this->form->addContent([$qrWidget]);
        }

        $refreshAction = new TAction([__CLASS__, 'onLoad'], ['key' => $bot->id, 'register_state' => 'false']);
        $createAction = new TAction([__CLASS__, 'onCreateInstance'], ['key' => $bot->id, 'register_state' => 'false']);
        $disconnectAction = new TAction([__CLASS__, 'onDisconnect'], ['key' => $bot->id, 'register_state' => 'false']);
        $openManagerAction = new TAction([__CLASS__, 'onOpenManager'], [
            'url' => $snapshot['manager_url'] ?? '',
            'register_state' => 'false'
        ]);

        if (empty($snapshot['exists'])) {
            $btnCreate = $this->form->addAction('Criar Instância', $createAction, 'fas:plus-circle green');
            $btnCreate->class = 'btn btn-sm btn-success';
        } else {
            $btnRefresh = $this->form->addAction('Atualizar QR/Status', $refreshAction, 'fas:sync blue');
            $btnRefresh->class = 'btn btn-sm btn-primary';

            $btnManager = $this->form->addAction('Abrir edição no Evolution', $openManagerAction, 'fas:external-link-alt');
            $btnManager->class = 'btn btn-sm btn-default';

            $btnDisconnect = $this->form->addAction('Desconectar', $disconnectAction, 'fas:unlink red');
            $btnDisconnect->class = 'btn btn-sm btn-warning';
        }

        $this->form->addHeaderActionLink('Fechar', new TAction([__CLASS__, 'onClose']), 'fa:times red');
        $this->addChannelSwitcher($bot, $channels, 'whatsapp');

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($this->form);

        parent::add($container);
    }

    private function buildTelegramView(AppBot $bot, array $snapshot, array $channels)
    {
        $this->clearChildren();

        $this->form = new BootstrapFormBuilder('form_AppBotConnect');
        $this->form->setFormTitle('Conexão Telegram');

        $bot_nome = new TEntry('bot_nome');
        $canal = new TEntry('canal');
        $bot_username = new TEntry('bot_username');
        $bot_id = new TEntry('bot_id');
        $status = new TEntry('status');
        $telegram_webhook_url = new TEntry('telegram_webhook_url');
        $telegram_webhook_secret = new TEntry('telegram_webhook_secret');
        $pending_update_count = new TEntry('pending_update_count');
        $last_error_message = new TText('last_error_message');

        $bot_nome->setValue($bot->nome);
        $canal->setValue('Telegram');
        $bot_username->setValue($snapshot['bot_username'] ?? $bot->telegram_bot_username ?? '');
        $bot_id->setValue($snapshot['bot_id'] ?? '');
        $status->setValue($snapshot['state'] ?? 'UNKNOWN');
        $telegram_webhook_url->setValue($snapshot['configured_webhook_url'] ?? $bot->telegram_webhook_url ?? '');
        $telegram_webhook_secret->setValue($bot->telegram_webhook_secret ?? '');
        $pending_update_count->setValue((string) ($snapshot['pending_update_count'] ?? 0));
        $last_error_message->setValue($snapshot['last_error_message'] ?? '');

        $bot_nome->setEditable(false);
        $canal->setEditable(false);
        $bot_username->setEditable(false);
        $bot_id->setEditable(false);
        $status->setEditable(false);
        $pending_update_count->setEditable(false);
        $last_error_message->setEditable(false);
        $telegram_webhook_secret->setProperty('type', 'password');

        $bot_nome->setSize('100%');
        $canal->setSize('100%');
        $bot_username->setSize('100%');
        $bot_id->setSize('100%');
        $status->setSize('100%');
        $telegram_webhook_url->setSize('100%');
        $telegram_webhook_secret->setSize('100%');
        $pending_update_count->setSize('100%');
        $last_error_message->setSize('100%', 80);

        $this->form->addFields([new TLabel('Bot')], [$bot_nome], [new TLabel('Canal')], [$canal]);
        $this->form->addFields([new TLabel('Bot username')], [$bot_username], [new TLabel('Bot ID')], [$bot_id]);
        $this->form->addFields([new TLabel('Status')], [$status], [new TLabel('Pendências')], [$pending_update_count]);
        $this->form->addFields([new TLabel('Webhook URL')], [$telegram_webhook_url]);
        $this->form->addFields([new TLabel('Webhook Secret')], [$telegram_webhook_secret]);
        $this->form->addFields([new TLabel('Último erro Telegram')], [$last_error_message]);

        if (!empty($snapshot['warning'])) {
            $this->form->addContent([new TLabel('Aviso'), $snapshot['warning']]);
        } elseif (!empty($snapshot['connected'])) {
            $this->form->addContent([new TLabel('Conectado'), 'Webhook do Telegram já está ativo.']);
        } else {
            $this->form->addContent([new TLabel('Sem webhook'), 'Configure a Webhook URL e clique em "Ativar/Atualizar Webhook".']);
        }

        $refreshAction = new TAction([__CLASS__, 'onLoad'], ['key' => $bot->id, 'canal' => 'telegram', 'register_state' => 'false']);
        $setWebhookAction = new TAction([__CLASS__, 'onSetTelegramWebhook'], ['key' => $bot->id, 'register_state' => 'false']);
        $deleteWebhookAction = new TAction([__CLASS__, 'onDeleteTelegramWebhook'], ['key' => $bot->id, 'register_state' => 'false']);

        $btnRefresh = $this->form->addAction('Atualizar Status', $refreshAction, 'fas:sync blue');
        $btnRefresh->class = 'btn btn-sm btn-primary';

        $btnSet = $this->form->addAction('Ativar/Atualizar Webhook', $setWebhookAction, 'fas:link green');
        $btnSet->class = 'btn btn-sm btn-success';

        $btnDelete = $this->form->addAction('Desativar Webhook', $deleteWebhookAction, 'fas:unlink red');
        $btnDelete->class = 'btn btn-sm btn-warning';

        $this->form->addHeaderActionLink('Fechar', new TAction([__CLASS__, 'onClose']), 'fa:times red');
        $this->addChannelSwitcher($bot, $channels, 'telegram');

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($this->form);

        parent::add($container);
    }

    private function buildQrWidget($qrPayload)
    {
        if (empty($qrPayload) || empty($qrPayload['value'])) {
            return 'QR Code indisponível no momento. Clique em "Atualizar QR/Status".';
        }

        $type = $qrPayload['type'] ?? 'text';
        $value = (string) $qrPayload['value'];

        if ($type === 'image_data_uri') {
            $img = new TElement('img');
            $img->src = $value;
            $img->style = 'max-width:320px; width:100%; border:1px solid #ddd; padding:8px; border-radius:8px;';
            return $img;
        }

        if ($type === 'image_base64') {
            $img = new TElement('img');
            $img->src = 'data:image/png;base64,' . $value;
            $img->style = 'max-width:320px; width:100%; border:1px solid #ddd; padding:8px; border-radius:8px;';
            return $img;
        }

        // Text QR payload: render as SVG using BaconQrCode
        $backend  = new \BaconQrCode\Renderer\Image\SvgImageBackEnd;
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(320, 1),
            $backend
        );
        $writer = new \BaconQrCode\Writer($renderer);
        $svg = $writer->writeString($value);

        $wrapper = new TElement('div');
        $wrapper->style = 'max-width:340px;';
        $wrapper->add($svg);

        $codeLabel = new TElement('pre');
        $codeLabel->style = 'white-space: pre-wrap; font-size:11px; margin-top:8px; color:#666;';
        $codeLabel->add($value);
        $wrapper->add($codeLabel);

        return $wrapper;
    }

    private function addChannelSwitcher(AppBot $bot, array $channels, $activeChannel)
    {
        if (count($channels) < 2) {
            return;
        }

        if (in_array('whatsapp', $channels, true)) {
            $label = $activeChannel === 'whatsapp' ? 'Canal: WhatsApp' : 'Ver WhatsApp';
            $icon = $activeChannel === 'whatsapp' ? 'fab:whatsapp green' : 'fab:whatsapp';
            $this->form->addHeaderActionLink(
                $label,
                new TAction([__CLASS__, 'onLoad'], ['key' => $bot->id, 'canal' => 'whatsapp', 'register_state' => 'false']),
                $icon
            );
        }

        if (in_array('telegram', $channels, true)) {
            $label = $activeChannel === 'telegram' ? 'Canal: Telegram' : 'Ver Telegram';
            $icon = $activeChannel === 'telegram' ? 'fab:telegram-plane blue' : 'fab:telegram-plane';
            $this->form->addHeaderActionLink(
                $label,
                new TAction([__CLASS__, 'onLoad'], ['key' => $bot->id, 'canal' => 'telegram', 'register_state' => 'false']),
                $icon
            );
        }
    }
}
