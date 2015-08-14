<?php
namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Manualchooser extends \Magento\Config\Block\System\Config\Form\Field
{

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->setElement($element);
        return $this->_getAddRowButtonHtml("Choose Products");
    }

    protected function _getAddRowButtonHtml($title)
    {
        $action = 'getManualProductChooser(\'' . $this->getUrl(
                '*/widget_chooser/product/form/manual_product_selector',
                array('_secure' => '@todo check for secure')
            ) . '?isAjax=true\'); return false;';

        return 'button';
//	        $this->getLayout()->createBlock('adminhtml/widget_button')
//            ->setType('button')
//            ->setLabel($this->__($title))
//            ->setOnClick($action)
//            ->toHtml();
    }
}
