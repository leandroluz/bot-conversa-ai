<?php
/**
 * AppBotActionForm
 *
 * @package    control
 * @subpackage app
 */
class AppBotActionForm extends TStandardForm
{
    protected $form;

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();

        parent::setTargetContainer('adianti_right_panel');

        $this->setDatabase('permission');
        $this->setActiveRecord('AppBotAction');
        $this->setAfterSaveAction(new TAction(['AppBotActionList', 'onReload']));

        $this->form = new BootstrapFormBuilder('form_AppBotAction');
        $this->form->setFormTitle('Ação do Bot');
        $this->form->enableClientValidation();

        $id = new TEntry('id');
        $app_bot_id = new TDBCombo('app_bot_id', 'permission', 'AppBot', 'id', 'nome', 'nome asc');
        $nome = new TEntry('nome');
        $tipo = new TCombo('tipo');
        $gatilho = new TEntry('gatilho');
        $resposta_fixa = new TText('resposta_fixa');
        $config_json = new TText('config_json');
        $ordem = new TEntry('ordem');
        $ativo = new TCombo('ativo');

        $tipo->addItems([
            'resposta_fixa' => 'Resposta fixa',
            'handoff_humano' => 'Encaminhar humano',
            'webhook' => 'Webhook',
        ]);
        $ativo->addItems(['Y' => 'Sim', 'N' => 'Não']);

        $this->form->addFields([new TLabel('Id')], [$id]);
        $this->form->addFields([new TLabel('Bot')], [$app_bot_id], [new TLabel('Nome da ação')], [$nome]);
        $this->form->addFields([new TLabel('Tipo')], [$tipo], [new TLabel('Ativo')], [$ativo], [new TLabel('Ordem')], [$ordem]);
        $this->form->addFields([new TLabel('Gatilhos (separados por vírgula)')], [$gatilho]);
        $this->form->addFields([new TLabel('Resposta fixa (quando aplicável)')], [$resposta_fixa]);
        $this->form->addFields([new TLabel('Config JSON (opcional)')], [$config_json]);

        $id->setEditable(false);

        $id->setSize('30%');
        $app_bot_id->setSize('100%');
        $nome->setSize('100%');
        $tipo->setSize('100%');
        $ativo->setSize('100%');
        $ordem->setSize('100%');
        $gatilho->setSize('100%');
        $resposta_fixa->setSize('100%', 120);
        $config_json->setSize('100%', 120);

        $app_bot_id->addValidation('Bot', new TRequiredValidator);
        $nome->addValidation('Nome da ação', new TRequiredValidator);
        $tipo->addValidation('Tipo', new TRequiredValidator);
        $gatilho->addValidation('Gatilhos', new TRequiredValidator);

        $btn = $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'far:save');
        $btn->class = 'btn btn-sm btn-primary';
        $this->form->addActionLink('Limpar', new TAction([$this, 'onEdit']), 'fa:eraser red');

        $this->form->addHeaderActionLink('Fechar', new TAction([$this, 'onClose']), 'fa:times red');

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($this->form);

        parent::add($container);
    }

    /**
     * on close
     */
    public static function onClose($param)
    {
        TScript::create("Template.closeRightPanel()");
    }

    /**
     * Set defaults for new records
     */
    public function onEdit($param)
    {
        parent::onEdit($param);

        if (empty($param['key'])) {
            $data = $this->form->getData();
            $data->ativo = $data->ativo ?? 'Y';
            $data->ordem = $data->ordem ?? '0';
            $data->tipo = $data->tipo ?? 'resposta_fixa';
            $data->config_json = $data->config_json ?? '{}';

            if (empty($data->app_bot_id) && !empty($param['app_bot_id'])) {
                $data->app_bot_id = $param['app_bot_id'];
            }

            $this->form->setData($data);
        }
    }

    /**
     * Validate optional json payload before save
     */
    public function onSave()
    {
        try {
            $this->form->validate();
            $data = $this->form->getData();

            $json = trim((string) ($data->config_json ?? ''));
            if ($json === '') {
                $data->config_json = '{}';
            } else {
                json_decode($json, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Config JSON inválido');
                }
                $data->config_json = $json;
            }

            $this->form->setData($data);
            parent::onSave();
        } catch (Exception $e) {
            $this->form->setData($this->form->getData());
            new TMessage('error', $e->getMessage());
        }
    }
}
