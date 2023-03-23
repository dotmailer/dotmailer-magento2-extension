<?php

namespace Dotdigitalgroup\Email\Observer\Frontend;

use Dotdigitalgroup\Email\Model\Customer\Account\Configuration;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;

class RemoveBlocks implements ObserverInterface
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Configuration $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Configuration $config,
        StoreManagerInterface $storeManager
    ) {
        $this->config = $config;
        $this->storeManager = $storeManager;
    }

    /**
     * Execute.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $websiteId = $this->storeManager->getWebsite()->getId();
        if (!$this->config->shouldRedirectToConnectorCustomerIndex($websiteId)) {
            return;
        }
        $layout = $observer->getLayout();
        $layout->unsetElement('customer-account-navigation-newsletter-subscriptions-link');
    }
}
