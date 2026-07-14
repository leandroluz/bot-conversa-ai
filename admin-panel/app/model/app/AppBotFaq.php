<?php
/**
 * AppBotFaq
 *
 * @package    model
 * @subpackage app
 */
class AppBotFaq extends TRecord
{
    const TABLENAME  = 'app.app_bot_faq';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'uuid'; // {uuid, serial, max}

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('app_bot_id');
        parent::addAttribute('pergunta');
        parent::addAttribute('resposta');
        parent::addAttribute('palavras_chave');
        parent::addAttribute('fonte_externa');
        parent::addAttribute('embedding_text');
        parent::addAttribute('embedding_array');
        parent::addAttribute('ordem');
        parent::addAttribute('ativo');
        parent::addAttribute('criado_em');
        parent::addAttribute('atualizado_em');
    }

    /**
     * Return bot reference
     */
    public function get_bot()
    {
        return new AppBot($this->app_bot_id);
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
}
