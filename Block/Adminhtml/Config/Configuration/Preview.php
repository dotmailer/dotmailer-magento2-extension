<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Configuration;

class Preview extends \Magento\Backend\Block\Template
{

    /**
     * @return string
     */
    public function getCurrencySymbol()
    {
        return $this->_storeManager->getStore()
            ->getCurrentCurrency()
            ->getCurrencySymbol();
    }

    /**
     * @return string
     */
    public function getImagePlaceholder()
    {
        return $this->getViewFileUrl('Dotdigitalgroup_Email::images/pimage.jpg');
    }
}
