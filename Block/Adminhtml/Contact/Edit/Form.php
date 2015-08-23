<?php

namespace Dotdititalgroup\Email\Block\Adminhtml\Contact\Edit;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{

    protected function _prepareForm()
    {
	    /** @var \Magento\Framework\Data\Form $form */
	    $form = $this->_formFactory->create(
		    ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
	    );
	    $form->setUseContainer(true);
	    $this->setForm($form);
	    return parent::_prepareForm();
    }

}
