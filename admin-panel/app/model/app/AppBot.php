<?php
/**
 * AppBot
 *
 * @package    model
 * @subpackage app
 */
class AppBot extends TRecord
{
    const TABLENAME  = 'app.app_bot';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'uuid'; // {uuid, serial, max}

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('system_unit_id');
        parent::addAttribute('nome');
        parent::addAttribute('canal');
        parent::addAttribute('evolution_instance');
        parent::addAttribute('evolution_instance_id');
        parent::addAttribute('evolution_api_url');
        parent::addAttribute('evolution_api_key');
        parent::addAttribute('telegram_bot_token');
        parent::addAttribute('telegram_bot_username');
        parent::addAttribute('telegram_webhook_secret');
        parent::addAttribute('telegram_webhook_url');
        parent::addAttribute('instrucoes');
        parent::addAttribute('modelo_llm');
        parent::addAttribute('temperatura');
        parent::addAttribute('usar_rag');
        parent::addAttribute('faq_top_k');
        parent::addAttribute('similaridade_minima');
        parent::addAttribute('ativo');
        parent::addAttribute('criado_em');
        parent::addAttribute('atualizado_em');
        parent::addAttribute('top_p');
        parent::addAttribute('max_tokens');
        parent::addAttribute('max_messages_context');
        parent::addAttribute('split_long_replies');
        parent::addAttribute('avoid_repetition');
        parent::addAttribute('wait_seconds');
        parent::addAttribute('human_handoff_pause_minutes');
        parent::addAttribute('allow_audio');
        parent::addAttribute('allow_image');
        parent::addAttribute('allow_pdf');
        parent::addAttribute('allow_code_interpreter');
        parent::addAttribute('allow_web_search');
    }

    /**
     * Return FAQs from this bot
     */
    public function get_faqs()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('app_bot_id', '=', $this->id));
        $criteria->setProperty('order', 'ordem');
        $criteria->setProperty('direction', 'asc');

        $repository = new TRepository('AppBotFaq');
        return $repository->load($criteria);
    }

    /**
     * Return configured actions from this bot
     */
    public function get_actions()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('app_bot_id', '=', $this->id));
        $criteria->setProperty('order', 'ordem');
        $criteria->setProperty('direction', 'asc');

        $repository = new TRepository('AppBotAction');
        return $repository->load($criteria);
    }

    /**
     * Keep timestamps consistent on writes
     */
    public function store()
    {
        $now = date('Y-m-d H:i:s');
        $this->atualizado_em = $now;

        if (empty($this->criado_em)) {
            $this->criado_em = $now;
        }

        parent::store();
    }

    /**
     * Normalize channels list from mixed input
     */
    public static function normalizeChannels($value)
    {
        $allowed = ['whatsapp', 'telegram'];
        $list = [];

        if (is_array($value)) {
            $list = $value;
        } else {
            $raw = trim((string) $value);
            if ($raw !== '') {
                $list = explode(',', $raw);
            }
        }

        $normalized = [];
        foreach ($list as $channel) {
            $channel = strtolower(trim((string) $channel));
            if ($channel !== '' && in_array($channel, $allowed, true)) {
                $normalized[] = $channel;
            }
        }

        $normalized = array_values(array_unique($normalized));

        usort($normalized, function ($a, $b) {
            $order = ['whatsapp' => 1, 'telegram' => 2];
            return ($order[$a] ?? 99) <=> ($order[$b] ?? 99);
        });

        return $normalized;
    }

    /**
     * Return channels as canonical comma-separated value
     */
    public static function serializeChannels($value)
    {
        $channels = self::normalizeChannels($value);
        return implode(',', $channels);
    }

    /**
     * Check if current bot has given channel
     */
    public function hasChannel($channel)
    {
        return in_array(
            strtolower(trim((string) $channel)),
            self::normalizeChannels($this->canal),
            true
        );
    }
}
