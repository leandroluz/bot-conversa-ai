<?php
/**
 * AppBotFaqList
 *
 * @package    control
 * @subpackage app
 */
class AppBotFaqList extends TStandardList
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

        parent::setDatabase('permission');
        parent::setActiveRecord('AppBotFaq');
        parent::setDefaultOrder('ordem', 'asc');
        parent::addFilterField('app_bot_id', '=', 'app_bot_id');
        parent::addFilterField('pergunta', 'like', 'pergunta');
        parent::addFilterField('ativo', '=', 'ativo');
        parent::setLimit(TSession::getValue(__CLASS__ . '_limit') ?? 20);

        $bot_items = [];
        try {
            TTransaction::open('permission');
            $bot_items = AppBot::getIndexedArray('id', 'nome');
            TTransaction::close();
        } catch (Exception $e) {
            if (TTransaction::get()) {
                TTransaction::rollback();
            }
        }

        $this->form = new BootstrapFormBuilder('form_search_AppBotFaq');
        $this->form->setFormTitle('FAQs dos Bots');

        $app_bot_id = new TCombo('app_bot_id');
        $pergunta = new TEntry('pergunta');
        $ativo = new TCombo('ativo');

        $app_bot_id->addItems($bot_items);
        $ativo->addItems(['' => 'Todos', 'Y' => 'Sim', 'N' => 'Não']);

        $this->form->addFields([new TLabel('Bot')], [$app_bot_id]);
        $this->form->addFields([new TLabel('Pergunta')], [$pergunta], [new TLabel('Ativo')], [$ativo]);

        $app_bot_id->setSize('100%');
        $pergunta->setSize('100%');
        $ativo->setSize('100%');

        $this->form->setData(TSession::getValue('AppBotFaq_filter_data'));

        $btn = $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(420);

        $column_bot = new TDataGridColumn('app_bot_id', 'Bot', 'left', '220');
        $column_pergunta = new TDataGridColumn('pergunta', 'Pergunta', 'left');
        $column_ordem = new TDataGridColumn('ordem', 'Ordem', 'center', '90');
        $column_ativo = new TDataGridColumn('ativo', 'Ativo', 'center', '90');

        $column_bot->setTransformer(function ($value) use ($bot_items) {
            return $bot_items[$value] ?? $value;
        });

        $column_pergunta->setTransformer(function ($value) {
            if ($value === null) {
                return '';
            }

            $text = trim((string) $value);
            if (strlen($text) > 120) {
                return substr($text, 0, 120) . '...';
            }

            return $text;
        });

        $column_ativo->setTransformer(function ($value) {
            return $value === 'Y' ? 'Sim' : 'Não';
        });

        $this->datagrid->addColumn($column_bot);
        $this->datagrid->addColumn($column_pergunta);
        $this->datagrid->addColumn($column_ordem);
        $this->datagrid->addColumn($column_ativo);

        $order_bot = new TAction([$this, 'onReload']);
        $order_bot->setParameter('order', 'app_bot_id');
        $column_bot->setAction($order_bot);

        $order_ordem = new TAction([$this, 'onReload']);
        $order_ordem->setParameter('order', 'ordem');
        $column_ordem->setAction($order_ordem);

        $action_edit = new TDataGridAction(['AppBotFaqForm', 'onEdit'], ['register_state' => 'false']);
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

        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup;
        $panel->add($this->datagrid);
        $panel->addFooter($this->pageNavigation);
        $panel->addHeaderActionLink('', new TAction(['AppBotFaqForm', 'onEdit'], ['register_state' => 'false']), 'fa:plus');

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add($this->form);
        $vbox->add($panel);

        parent::add($vbox);
    }
}
