<?php
namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Fallbackchooser extends \Magento\Config\Block\System\Config\Form\Field
{

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->setElement($element);
        return $this->_getAddRowButtonHtml("Choose Products");
    }

    protected function _getAddRowButtonHtml($title)
    {
	    return $title;

        $action = 'getFallbackProductChooser(\'' . Mage::getUrl(
                '*/widget_chooser/product/form/fallback_product_selector',
                array('_secure' => Mage::app()->getStore()->isAdminUrlSecure())
            ) . '?isAjax=true\'); return false;';

        return $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setLabel($this->__($title))
            ->setOnClick($action)
            ->toHtml();
    }
}
