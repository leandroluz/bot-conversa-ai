<?php
/**
 * AppBotActionList
 *
 * @package    control
 * @subpackage app
 */
class AppBotActionList extends TStandardList
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
        parent::setActiveRecord('AppBotAction');
        parent::setDefaultOrder('ordem', 'asc');
        parent::addFilterField('app_bot_id', '=', 'app_bot_id');
        parent::addFilterField('nome', 'like', 'nome');
        parent::addFilterField('tipo', '=', 'tipo');
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

        $this->form = new BootstrapFormBuilder('form_search_AppBotAction');
        $this->form->setFormTitle('Ações dos Bots');

        $app_bot_id = new TCombo('app_bot_id');
        $nome = new TEntry('nome');
        $tipo = new TCombo('tipo');
        $ativo = new TCombo('ativo');

        $app_bot_id->addItems($bot_items);
        $tipo->addItems([
            '' => 'Todos',
            'resposta_fixa' => 'Resposta fixa',
            'handoff_humano' => 'Encaminhar humano',
            'webhook' => 'Webhook',
        ]);
        $ativo->addItems(['' => 'Todos', 'Y' => 'Sim', 'N' => 'Não']);

        $this->form->addFields([new TLabel('Bot')], [$app_bot_id]);
        $this->form->addFields([new TLabel('Nome da ação')], [$nome]);
        $this->form->addFields([new TLabel('Tipo')], [$tipo], [new TLabel('Ativo')], [$ativo]);

        $app_bot_id->setSize('100%');
        $nome->setSize('100%');
        $tipo->setSize('100%');
        $ativo->setSize('100%');

        $this->form->setData(TSession::getValue('AppBotAction_filter_data'));

        $btn = $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(420);

        $column_bot = new TDataGridColumn('app_bot_id', 'Bot', 'left', '220');
        $column_nome = new TDataGridColumn('nome', 'Ação', 'left');
        $column_tipo = new TDataGridColumn('tipo', 'Tipo', 'center', '140');
        $column_gatilho = new TDataGridColumn('gatilho', 'Gatilho', 'left', '240');
        $column_ordem = new TDataGridColumn('ordem', 'Ordem', 'center', '90');
        $column_ativo = new TDataGridColumn('ativo', 'Ativo', 'center', '90');

        $column_bot->setTransformer(function ($value) use ($bot_items) {
            return $bot_items[$value] ?? $value;
        });

        $column_tipo->setTransformer(function ($value) {
            $map = [
                'resposta_fixa' => 'Resposta fixa',
                'handoff_humano' => 'Encaminhar humano',
                'webhook' => 'Webhook',
            ];
            return $map[$value] ?? $value;
        });

        $column_gatilho->setTransformer(function ($value) {
            $text = trim((string) $value);
            if (strlen($text) > 60) {
                return substr($text, 0, 60) . '...';
            }
            return $text;
        });

        $column_ativo->setTransformer(function ($value) {
            return $value === 'Y' ? 'Sim' : 'Não';
        });

        $this->datagrid->addColumn($column_bot);
        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_tipo);
        $this->datagrid->addColumn($column_gatilho);
        $this->datagrid->addColumn($column_ordem);
        $this->datagrid->addColumn($column_ativo);

        $order_bot = new TAction([$this, 'onReload']);
        $order_bot->setParameter('order', 'app_bot_id');
        $column_bot->setAction($order_bot);

        $order_ordem = new TAction([$this, 'onReload']);
        $order_ordem->setParameter('order', 'ordem');
        $column_ordem->setAction($order_ordem);

        $action_edit = new TDataGridAction(['AppBotActionForm', 'onEdit'], ['register_state' => 'false']);
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
        $panel->addHeaderActionLink('', new TAction(['AppBotActionForm', 'onEdit'], ['register_state' => 'false']), 'fa:plus');

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add($this->form);
        $vbox->add($panel);

        parent::add($vbox);
    }
}
