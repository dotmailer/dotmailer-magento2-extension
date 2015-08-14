<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Addressbookbutton extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getAddRowButtonHtml($title)
    {
        return $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setLabel($this->__($title))
            ->setOnClick("createAddressbook(this.form, this);")
            ->toHtml();
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $originalData = $element->getOriginalData();

        return $this->_getAddRowButtonHtml($this->__($originalData['button_label']));
    }
}