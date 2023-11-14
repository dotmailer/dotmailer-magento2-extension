<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Configuration;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;

class Preview extends \Magento\Backend\Block\Template
{
    /**
     * @var string
     */
    protected $_template = 'Dotdigitalgroup_Email::system/preview.phtml';

    /**
     * Get currency symbol.
     *
     * @return string|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCurrencySymbol()
    {
        /** @var Store $store */
        $store = $this->_storeManager->getStore();
        return $store->getCurrentCurrency()
            ->getCurrencySymbol();
    }

    /**
     * Get image placeholder.
     *
     * @return string|null
     */
    public function getImagePlaceholder(): ?string
    {
        return $this->getViewFileUrl('Dotdigitalgroup_Email::images/pimage.jpg');
    }
}
