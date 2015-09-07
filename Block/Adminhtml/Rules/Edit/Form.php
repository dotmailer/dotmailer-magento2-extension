<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Rules\Edit;

class Form extends \Magento\Backend\Block\Widget\Container
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('edit_form');
        $this->setTitle(__('Rule Information'));
    }

    protected function _prepareForm()
    {
	    //@todo fix the form
        $form = '';//new Varien_Data_Form(array('id' => 'edit_form', 'action' => $this->getUrl('adminhtml/email_rules/save'), 'method' => 'post'));
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }


}
