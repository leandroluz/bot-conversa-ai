<?php
/**
 * AppBotAction
 *
 * @package    model
 * @subpackage app
 */
class AppBotAction extends TRecord
{
    const TABLENAME  = 'app.app_bot_action';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'uuid'; // {uuid, serial, max}

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('app_bot_id');
        parent::addAttribute('nome');
        parent::addAttribute('tipo');
        parent::addAttribute('gatilho');
        parent::addAttribute('resposta_fixa');
        parent::addAttribute('config_json');
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
