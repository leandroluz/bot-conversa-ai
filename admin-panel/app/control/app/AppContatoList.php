<?php
/**
 * AppContatoList
 *
 * @package    control
 * @subpackage app
 */
class AppContatoList extends TStandardList
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

        parent::setDatabase('app');        // connection name
        parent::setActiveRecord('AppContato');    // active record
        parent::setDefaultOrder('criado_em', 'desc');
        parent::addFilterField('nome', 'like', 'nome');
        parent::addFilterField('telefone', 'like', 'telefone');
        parent::setLimit(TSession::getValue(__CLASS__ . '_limit') ?? 20);

        // search form
        $this->form = new BootstrapFormBuilder('form_search_AppContato');
        $this->form->setFormTitle('Contatos');

        $nome = new TEntry('nome');
        $telefone = new TEntry('telefone');

        $this->form->addFields([new TLabel('Nome')], [$nome]);
        $this->form->addFields([new TLabel('Telefone')], [$telefone]);

        $nome->setSize('100%');
        $telefone->setSize('100%');

        $this->form->setData(TSession::getValue('AppContato_filter_data'));

        $btn = $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';

        // datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $column_nome = new TDataGridColumn('nome', 'Nome', 'left');
        $column_telefone = new TDataGridColumn('telefone', 'Telefone', 'left', '160');
        $column_foto = new TDataGridColumn('foto_url', 'Foto', 'center', '80');
        $column_criado = new TDataGridColumn('criado_em', 'Criado em', 'center', '160');

        $column_foto->setTransformer(function ($value) {
            if (empty($value)) {
                return '';
            }
            $img = new TElement('img');
            $img->src = $value;
            $img->style = 'width:40px;height:40px;border-radius:50%;object-fit:cover;';
            return $img;
        });

        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_telefone);
        $this->datagrid->addColumn($column_foto);
        $this->datagrid->addColumn($column_criado);

        $order_nome = new TAction([$this, 'onReload']);
        $order_nome->setParameter('order', 'nome');
        $column_nome->setAction($order_nome);

        $order_telefone = new TAction([$this, 'onReload']);
        $order_telefone->setParameter('order', 'telefone');
        $column_telefone->setAction($order_telefone);

        $order_criado = new TAction([$this, 'onReload']);
        $order_criado->setParameter('order', 'criado_em');
        $column_criado->setAction($order_criado);

        // actions
        $action_edit = new TDataGridAction(['AppContatoForm', 'onEdit'], ['register_state' => 'false']);
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel('Editar');
        $action_edit->setImage('far:edit blue');
        $action_edit->setField('id');
        $this->datagrid->addAction($action_edit);

        $action_del = new TDataGridAction([$this, 'onDelete']);
        $action_del->setButtonClass('btn btn-default');
        $action_del->setLabel('Excluir');
        $action_del->setImage('far:trash-alt red');
        $action_del->setField('id');
        $this->datagrid->addAction($action_del);

        $this->datagrid->createModel();

        // page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup;
        $panel->add($this->datagrid);
        $panel->addFooter($this->pageNavigation);
        $panel->addHeaderActionLink('', new TAction(['AppContatoForm', 'onEdit'], ['register_state' => 'false']), 'fa:plus');

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add($this->form);
        $vbox->add($panel);

        parent::add($vbox);
    }
}
