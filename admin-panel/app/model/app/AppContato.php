<?php
/**
 * AppContato
 *
 * @package    model
 * @subpackage app
 */
class AppContato extends TRecord
{
    const TABLENAME  = 'app_contato';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'uuid'; // {uuid, serial, max}

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('telefone');
        parent::addAttribute('foto_url');
        parent::addAttribute('criado_em');
    }

    /**
     * Return sessions from this contact
     */
    public function get_sessions()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('contato_id', '=', $this->id));
        $criteria->setProperty('order', 'criado_em');
        $criteria->setProperty('direction', 'desc');

        $repository = new TRepository('AppSession');
        return $repository->load($criteria);
    }

    /**
     * Return messages from this contact
     */
    public function get_mensagens()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('contato_id', '=', $this->id));
        $criteria->setProperty('order', 'criado_em');
        $criteria->setProperty('direction', 'desc');

        $repository = new TRepository('AppMensagem');
        return $repository->load($criteria);
    }

    /**
     * Return last message from this contact
     */
    public function get_ultima_mensagem()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('contato_id', '=', $this->id));
        $criteria->setProperty('order', 'criado_em');
        $criteria->setProperty('direction', 'desc');
        $criteria->setProperty('limit', 1);

        $repository = new TRepository('AppMensagem');
        $items = $repository->load($criteria);
        return $items ? $items[0] : null;
    }
}
