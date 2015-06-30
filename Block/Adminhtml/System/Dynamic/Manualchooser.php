<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Manualchooser extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        return $this->_getAddRowButtonHtml($this->__("Choose Products"));
    }

    protected function _getAddRowButtonHtml($title)
    {
        $action = 'getManualProductChooser(\'' . Mage::getUrl(
                '*/widget_chooser/product/form/manual_product_selector',
                array('_secure' => Mage::app()->getStore()->isAdminUrlSecure())
            ) . '?isAjax=true\'); return false;';

        return $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setLabel($this->__($title))
            ->setOnClick($action)
            ->toHtml();
    }
}
