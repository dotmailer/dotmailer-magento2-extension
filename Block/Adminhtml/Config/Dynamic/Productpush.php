<?php
namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Productpush extends \Magento\Config\Block\System\Config\Form\Field
{
	public function __construct(
		\Dotdigitalgroup\Email\Helper\Data $dataHelper,
		\Magento\Backend\Block\Template\Context $context
	)
	{
		$this->_dataHelper = $dataHelper;

		parent::__construct($context);
	}

	/** label */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
	    //generate base url
	    $baseUrl = $this->_dataHelper->generateDynamicUrl();
        $passcode  = $this->_dataHelper->getPasscode();

        if (!strlen($passcode))
	        $passcode = '[PLEASE SET UP A PASSCODE]';

	    //full url for dynamic content
        $text = sprintf('%sconnector/product/push/code/%s', $baseUrl, $passcode);
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}