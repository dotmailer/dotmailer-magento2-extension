<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic\Wishlist;

class Content extends \Magento\Config\Block\System\Config\Form\Field
{

    protected $_dataHelper;

    /**
     * Content constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $dataHelper
     * @param \Magento\Backend\Block\Template\Context $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $dataHelper,
        \Magento\Backend\Block\Template\Context $context
    ) {
        $this->_dataHelper = $dataHelper;

        parent::__construct($context);
    }


    protected function _getElementHtml(
        \Magento\Framework\Data\Form\Element\AbstractElement $element
    ) {
        //passcode to append for url
        $passcode = $this->_dataHelper->getPasscode();
        //last order id witch information will be generated
        $customerId = $this->_dataHelper->getMappedCustomerId();

        if ( ! strlen($passcode)) {
            $passcode = '[PLEASE SET UP A PASSCODE]';
        }
        if ( ! $customerId) {
            $customerId = '[PLEASE MAP THE CUSTOMER ID]';
        }

        //generate the base url and display for default store id
        $baseUrl = $this->_dataHelper->generateDynamicUrl();

        //display the full url
        $text = sprintf('%sconnector/email/wishlist/code/%s/customer_id/@%s@',
            $baseUrl, $passcode, $customerId);
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }

}