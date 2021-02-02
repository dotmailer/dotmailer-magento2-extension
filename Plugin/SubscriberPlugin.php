<?php

namespace Dotdigitalgroup\Email\Plugin;

use Dotdigitalgroup\Email\Helper\Config;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Newsletter disable subscriber email depending on settings value.
 */
class SubscriberPlugin
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * SubscriberPlugin constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param callable $proceed
     * @return \Magento\Newsletter\Model\Subscriber
     */
    public function aroundSendConfirmationSuccessEmail(
        \Magento\Newsletter\Model\Subscriber $subscriber,
        callable $proceed
    ) {
        $storeId = $subscriber->getStoreId();
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();

        //scope config for sending newsletter is disabled
        if (!$this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_DISABLE_NEWSLETTER_SUCCESS,
            ScopeInterface::SCOPE_WEBSITES,
            $websiteId
        )
        ) {
            return $proceed();
        }

        return $subscriber;
    }
}
