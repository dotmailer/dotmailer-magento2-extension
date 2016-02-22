<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config;

class Ajaxvalidate extends \Magento\Config\Block\System\Config\Form\Field
{
    public function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element){



        return $element->getAfterElementHtml();
    }
}