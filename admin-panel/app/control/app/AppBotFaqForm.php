<?php
/**
 * AppBotFaqForm
 *
 * @package    control
 * @subpackage app
 */
class AppBotFaqForm extends TStandardForm
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
        $this->setActiveRecord('AppBotFaq');
        $this->setAfterSaveAction(new TAction(['AppBotFaqList', 'onReload']));

        $this->form = new BootstrapFormBuilder('form_AppBotFaq');
        $this->form->setFormTitle('FAQ do Bot');
        $this->form->enableClientValidation();

        $id = new TEntry('id');
        $app_bot_id = new TDBCombo('app_bot_id', 'permission', 'AppBot', 'id', 'nome', 'nome asc');
        $ordem = new TEntry('ordem');
        $ativo = new TCombo('ativo');
        $pergunta = new TText('pergunta');
        $resposta = new TText('resposta');
        $palavras_chave = new TEntry('palavras_chave');
        $fonte_externa = new TEntry('fonte_externa');

        $ativo->addItems(['Y' => 'Sim', 'N' => 'Não']);

        $this->form->addFields([new TLabel('Id')], [$id]);
        $this->form->addFields([new TLabel('Bot')], [$app_bot_id], [new TLabel('Ordem')], [$ordem], [new TLabel('Ativo')], [$ativo]);
        $this->form->addFields([new TLabel('Pergunta')], [$pergunta]);
        $this->form->addFields([new TLabel('Resposta')], [$resposta]);
        $this->form->addFields([new TLabel('Palavras-chave (separadas por vírgula)')], [$palavras_chave]);
        $this->form->addFields([new TLabel('Fonte externa (opcional)')], [$fonte_externa]);

        $id->setEditable(false);

        $id->setSize('30%');
        $app_bot_id->setSize('100%');
        $ordem->setSize('100%');
        $ativo->setSize('100%');
        $pergunta->setSize('100%', 100);
        $resposta->setSize('100%', 150);
        $palavras_chave->setSize('100%');
        $fonte_externa->setSize('100%');

        $app_bot_id->addValidation('Bot', new TRequiredValidator);
        $pergunta->addValidation('Pergunta', new TRequiredValidator);
        $resposta->addValidation('Resposta', new TRequiredValidator);

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

            if (empty($data->app_bot_id) && !empty($param['app_bot_id'])) {
                $data->app_bot_id = $param['app_bot_id'];
            }

            $this->form->setData($data);
        }
    }
}
