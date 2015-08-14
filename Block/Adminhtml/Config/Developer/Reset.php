<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Developer;

class Reset extends \Magento\Config\Block\System\Config\Form\Field
{

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->setElement($element);
        return $this->_getAddRowButtonHtml("Run Now");
    }

    protected function _getAddRowButtonHtml($title)
    {

	    return $title;

        $url = Mage::helper('adminhtml')->getUrl("*/connector/reset");

        return $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setLabel($this->__($title))
            ->setOnClick("window.location.href='" . $url . "'")
            ->toHtml();
    }
}
