<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Configuration;

class Preview extends \Magento\Backend\Block\Template
{
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getCurrencySymbol()
    {
        return $this->_storeManager->getStore()->getCurrentCurrency()->getCurrencySymbol();
    }

    public function getImagePlaceholder()
    {
        return $this->getViewFileUrl('Dotdigitalgroup_Email::images/pimage.jpg');
    }
}
