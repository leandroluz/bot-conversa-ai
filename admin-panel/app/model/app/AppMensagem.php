<?php
/**
 * AppMensagem
 *
 * @package    model
 * @subpackage app
 */
class AppMensagem extends TRecord
{
    const TABLENAME  = 'app_mensagem';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'uuid'; // {uuid, serial, max}

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('session_id');
        parent::addAttribute('contato_id');
        parent::addAttribute('remetente');
        parent::addAttribute('mensagem');
        parent::addAttribute('criado_em');
    }

    /**
     * Return session
     */
    public function get_session()
    {
        return AppSession::find($this->session_id);
    }

    /**
     * Return contact
     */
    public function get_contato()
    {
        if ($this->contato_id)
        {
            return AppContato::find($this->contato_id);
        }

        $session = $this->get_session();
        return $session ? $session->get_contato() : null;
    }
}
