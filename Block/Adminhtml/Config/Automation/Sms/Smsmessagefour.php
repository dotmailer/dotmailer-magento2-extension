<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Automation\Sms;
class Smsmessagefour extends \Magento\Config\Block\System\Config\Form\Field
{
    const DEFAULT_TEXT = 'Default SMS Text';


    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {

	    $element->setData('placeholder', self::DEFAULT_TEXT);
        $element->setData('after_element_html',
            "<a href='#' onclick=\"injectText('connector_sms_sms_four_message', '{{var order_number}}');return false;\">Insert Order Number</a>
            <a href='#' onclick=\"injectText('connector_sms_sms_four_message', '{{var customer_name}}');return false;\">Insert Customer Name</a>"
        );
        return parent::_getElementHtml($element);
    }


}