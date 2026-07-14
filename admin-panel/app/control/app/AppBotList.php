<?php
/**
 * AppBotList
 *
 * @package    control
 * @subpackage app
 */
class AppBotList extends TStandardList
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
        parent::setActiveRecord('AppBot');
        parent::setDefaultOrder('nome', 'asc');
        parent::addFilterField('nome', 'like', 'nome');
        parent::addFilterField('system_unit_id', '=', 'system_unit_id');
        parent::addFilterField('canal', 'like', 'canal');
        parent::addFilterField('ativo', '=', 'ativo');
        parent::setLimit(TSession::getValue(__CLASS__ . '_limit') ?? 20);

        $unit_items = [];
        try {
            TTransaction::open('permission');
            $unit_items = SystemUnit::getIndexedArray('id', 'name');
            TTransaction::close();
        } catch (Exception $e) {
            if (TTransaction::get()) {
                TTransaction::rollback();
            }
        }

        $this->form = new BootstrapFormBuilder('form_search_AppBot');
        $this->form->setFormTitle('Bots de Atendimento');

        $nome = new TEntry('nome');
        $system_unit_id = new TCombo('system_unit_id');
        $canal = new TCombo('canal');
        $ativo = new TCombo('ativo');

        $system_unit_id->addItems($unit_items);
        $canal->addItems([
            '' => 'Todos',
            '%whatsapp%' => 'WhatsApp',
            '%telegram%' => 'Telegram',
        ]);
        $ativo->addItems(['' => 'Todos', 'Y' => 'Sim', 'N' => 'Não']);

        $this->form->addFields([new TLabel('Nome')], [$nome]);
        $this->form->addFields([new TLabel('Unidade')], [$system_unit_id], [new TLabel('Canal')], [$canal], [new TLabel('Ativo')], [$ativo]);

        $nome->setSize('100%');
        $system_unit_id->setSize('100%');
        $canal->setSize('100%');
        $ativo->setSize('100%');

        $this->form->setData(TSession::getValue('AppBot_filter_data'));

        $btn = $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(380);

        $column_nome = new TDataGridColumn('nome', 'Nome', 'left');
        $column_unidade = new TDataGridColumn('system_unit_id', 'Unidade', 'left', '230');
        $column_canal = new TDataGridColumn('canal', 'Canal', 'left', '120');
        $column_instancia = new TDataGridColumn('evolution_instance', 'Instância Evolution', 'left', '180');
        $column_modelo = new TDataGridColumn('modelo_llm', 'Modelo LLM', 'center', '120');
        $column_rag = new TDataGridColumn('usar_rag', 'RAG', 'center', '80');
        $column_ativo = new TDataGridColumn('ativo', 'Ativo', 'center', '80');

        $column_unidade->setTransformer(function ($value) use ($unit_items) {
            return $unit_items[$value] ?? $value;
        });

        $column_canal->setTransformer(function ($value) {
            $channels = AppBot::normalizeChannels($value);
            $labels = [];

            if (in_array('whatsapp', $channels, true)) {
                $labels[] = 'WhatsApp';
            }
            if (in_array('telegram', $channels, true)) {
                $labels[] = 'Telegram';
            }

            return !empty($labels) ? implode(' + ', $labels) : '-';
        });

        $column_rag->setTransformer(function ($value) {
            return $value === 'Y' ? 'Sim' : 'Não';
        });

        $column_ativo->setTransformer(function ($value) {
            return $value === 'Y' ? 'Sim' : 'Não';
        });

        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_unidade);
        $this->datagrid->addColumn($column_canal);
        $this->datagrid->addColumn($column_instancia);
        $this->datagrid->addColumn($column_modelo);
        $this->datagrid->addColumn($column_rag);
        $this->datagrid->addColumn($column_ativo);

        $order_nome = new TAction([$this, 'onReload']);
        $order_nome->setParameter('order', 'nome');
        $column_nome->setAction($order_nome);

        $order_unidade = new TAction([$this, 'onReload']);
        $order_unidade->setParameter('order', 'system_unit_id');
        $column_unidade->setAction($order_unidade);

        $action_edit = new TDataGridAction(['AppBotForm', 'onEdit'], ['register_state' => 'false']);
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel('Editar');
        $action_edit->setImage('far:edit blue');
        $action_edit->setField('id');
        $this->datagrid->addAction($action_edit);

        $action_connect_whatsapp = new TDataGridAction(['AppBotConnectForm', 'onLoad'], ['register_state' => 'false', 'canal' => 'whatsapp']);
        $action_connect_whatsapp->setButtonClass('btn btn-default');
        $action_connect_whatsapp->setLabel('Conectar WhatsApp');
        $action_connect_whatsapp->setImage('fab:whatsapp green');
        $action_connect_whatsapp->setField('id');
        $action_connect_whatsapp->setDisplayCondition([$this, 'onDisplayWhatsappConnect']);
        $this->datagrid->addAction($action_connect_whatsapp);

        $action_connect_telegram = new TDataGridAction(['AppBotConnectForm', 'onLoad'], ['register_state' => 'false', 'canal' => 'telegram']);
        $action_connect_telegram->setButtonClass('btn btn-default');
        $action_connect_telegram->setLabel('Conectar Telegram');
        $action_connect_telegram->setImage('fab:telegram-plane blue');
        $action_connect_telegram->setField('id');
        $action_connect_telegram->setDisplayCondition([$this, 'onDisplayTelegramConnect']);
        $this->datagrid->addAction($action_connect_telegram);

        $action_actions = new TDataGridAction(['AppBotActionList', 'onReload']);
        $action_actions->setButtonClass('btn btn-default');
        $action_actions->setLabel('Ações');
        $action_actions->setImage('fas:bolt purple');
        $action_actions->setField('id');
        $action_actions->setParameter('app_bot_id', '{id}');
        $this->datagrid->addAction($action_actions);

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
        $panel->addHeaderActionLink('', new TAction(['AppBotForm', 'onEdit'], ['register_state' => 'false']), 'fa:plus');

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add($this->form);
        $vbox->add($panel);

        parent::add($vbox);
    }

    public function onDisplayWhatsappConnect($object)
    {
        return in_array('whatsapp', AppBot::normalizeChannels($object->canal ?? ''), true);
    }

    public function onDisplayTelegramConnect($object)
    {
        return in_array('telegram', AppBot::normalizeChannels($object->canal ?? ''), true);
    }
}
