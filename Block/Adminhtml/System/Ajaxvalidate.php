<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Ajaxvalidate extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    public function _getElementHtml(Varien_Data_Form_Element_Abstract$element){

        $element->setData('onchange', "apiValidation(this.form, this)");

        return parent::_getElementHtml($element);

    }
}