<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Automation_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{

    /**
	 * Contact Form.
	 * @return Mage_Adminhtml_Block_Widget_Form
	 * @throws Exception
	 */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
                'id'         => 'edit_form',
                'action'     => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'), 'store' => $this->getRequest()->getParam('store'))),
                'method'     => 'post',
                'enctype'    => 'multipart/form-data'
            )
        );
        $form->setUseContainer(true);

        $this->setForm($form);
        return parent::_prepareForm();
    }

}