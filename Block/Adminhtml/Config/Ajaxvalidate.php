<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config;

class Ajaxvalidate extends \Magento\Config\Block\System\Config\Form\Field
{
    public function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element){

//	    $element->setData('onchange', "apiValidation(this.form, this)");

        return parent::_getElementHtml($element);
    }
}