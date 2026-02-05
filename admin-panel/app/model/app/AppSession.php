<?php
/**
 * AppSession
 *
 * @package    model
 * @subpackage app
 */
class AppSession extends TRecord
{
    const TABLENAME  = 'app_session';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'uuid'; // {uuid, serial, max}

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('contato_id');
        parent::addAttribute('canal');
        parent::addAttribute('identificador_externo');
        parent::addAttribute('criado_em');
    }

    /**
     * Return contact
     */
    public function get_contato()
    {
        return AppContato::find($this->contato_id);
    }

    /**
     * Return messages from this session
     */
    public function get_mensagens()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('session_id', '=', $this->id));
        $criteria->setProperty('order', 'criado_em');
        $criteria->setProperty('direction', 'asc');

        $repository = new TRepository('AppMensagem');
        return $repository->load($criteria);
    }
}
