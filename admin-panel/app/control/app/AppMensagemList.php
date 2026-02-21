<?php
/**
 * AppMensagemList
 *
 * @package    control
 * @subpackage app
 */
class AppMensagemList extends TStandardList
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    protected $formgrid;
    protected $deleteButton;
    protected $transformCallback;

    /**
     * Page constructor
     */
    public function __construct()
    {
        parent::__construct();

        parent::setDatabase('app');
        parent::setActiveRecord('AppMensagem');
        parent::setDefaultOrder('criado_em', 'desc');
        parent::addFilterField('(SELECT c.nome FROM app.app_contato c WHERE c.id = app.app_mensagem.contato_id)', 'like', 'contato_nome');
        parent::addFilterField('remetente', 'like', 'remetente');
        parent::setLimit(TSession::getValue(__CLASS__ . '_limit') ?? 20);

        // search form
        $this->form = new BootstrapFormBuilder('form_search_AppMensagem');
        $this->form->setFormTitle('Mensagens');

        $contato_nome = new TEntry('contato_nome');
        $remetente = new TEntry('remetente');

        $this->form->addFields([new TLabel('Nome do contato')], [$contato_nome]);
        $this->form->addFields([new TLabel('Remetente')], [$remetente]);

        $contato_nome->setSize('100%');
        $remetente->setSize('100%');

        $this->form->setData(TSession::getValue('AppMensagem_filter_data'));

        $btn = $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';

        // datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(420);

        $column_contato = new TDataGridColumn('contato_id', 'Contato', 'left', '200');
        $column_remetente = new TDataGridColumn('remetente', 'Remetente', 'center', '130');
        $column_mensagem = new TDataGridColumn('mensagem', 'Mensagem', 'left');
        $column_criado = new TDataGridColumn('criado_em', 'Criado em', 'center', '170');

        $column_contato->setTransformer(function ($value, $object) {
            $contato = $object->get_contato();
            return $contato ? $contato->nome : '-';
        });

        $column_mensagem->setTransformer(function ($value) {
            if ($value === null) {
                return '';
            }

            $text = trim((string) $value);
            if (strlen($text) > 160) {
                return substr($text, 0, 160) . '...';
            }

            return $text;
        });

        $this->datagrid->addColumn($column_contato);
        $this->datagrid->addColumn($column_remetente);
        $this->datagrid->addColumn($column_mensagem);
        $this->datagrid->addColumn($column_criado);

        $order_contato = new TAction([$this, 'onReload']);
        $order_contato->setParameter('order', 'contato_id');
        $column_contato->setAction($order_contato);

        $order_remetente = new TAction([$this, 'onReload']);
        $order_remetente->setParameter('order', 'remetente');
        $column_remetente->setAction($order_remetente);

        $order_criado = new TAction([$this, 'onReload']);
        $order_criado->setParameter('order', 'criado_em');
        $column_criado->setAction($order_criado);

        $this->datagrid->createModel();

        // page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup;
        $panel->add($this->datagrid);
        $panel->addFooter($this->pageNavigation);

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add($this->form);
        $vbox->add($panel);

        parent::add($vbox);
    }
}
