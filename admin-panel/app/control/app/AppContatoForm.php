<?php
/**
 * AppContatoForm
 *
 * @package    control
 * @subpackage app
 */
class AppContatoForm extends TStandardForm
{
    protected $form;

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();

        parent::setTargetContainer('adianti_right_panel');

        $this->setDatabase('app');
        $this->setActiveRecord('AppContato');
        $this->setAfterSaveAction(new TAction(['AppContatoList', 'onReload']));

        $this->form = new BootstrapFormBuilder('form_AppContato');
        $this->form->setFormTitle('Contato');
        $this->form->enableClientValidation();

        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $telefone = new TEntry('telefone');
        $foto_url = new TEntry('foto_url');
        $criado_em = new TEntry('criado_em');

        $this->form->addFields([new TLabel('Id')], [$id]);
        $this->form->addFields([new TLabel('Nome')], [$nome]);
        $this->form->addFields([new TLabel('Telefone')], [$telefone]);
        $this->form->addFields([new TLabel('Foto URL')], [$foto_url]);
        $this->form->addFields([new TLabel('Criado em')], [$criado_em]);

        $id->setEditable(false);
        $criado_em->setEditable(false);

        $id->setSize('30%');
        $nome->setSize('100%');
        $telefone->setSize('100%');
        $foto_url->setSize('100%');
        $criado_em->setSize('50%');

        $nome->addValidation('Nome', new TRequiredValidator);
        $telefone->addValidation('Telefone', new TRequiredValidator);

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
}
