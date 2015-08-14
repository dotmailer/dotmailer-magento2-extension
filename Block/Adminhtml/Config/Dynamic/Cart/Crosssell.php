<?php
namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic\Cart;

class Crosssell extends \Magento\Config\Block\System\Config\Form\Field
{
	public function __construct(
		\Dotdigitalgroup\Email\Helper\Data $dataHelper,
		\Magento\Backend\Block\Template\Context $context
	)
	{
		$this->_dataHelper = $dataHelper;

		parent::__construct($context);
	}

	protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        //base url
	    $baseUrl = $this->_dataHelper->generateDynamicUrl();
	    //config passcode
        $passcode = $this->_dataHelper->getPasscode();
        //last quote id for dynamic page
	    $lastQuoteId = $this->_dataHelper->getLastQuoteId();

        if (!strlen($passcode))
	        $passcode = '[PLEASE SET UP A PASSCODE]';
	    //alert message for last order id is not mapped
        if (!$lastQuoteId)
            $lastQuoteId = '[PLEASE MAP THE LAST QUOTE ID]';

	    //full url for dynamic content
        $text =   sprintf('%sconnector/quoteproducts/crosssell/code/%s/quote_id/@%s@', $baseUrl, $passcode,  $lastQuoteId);
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}