<?php

namespace Dotdigitalgroup\Email\Plugin;

use Dotdigitalgroup\Email\Helper\Config;
use Magento\Store\Model\ScopeInterface;

/**
 * Disable customer email depending on settings value.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class CustomerEmailNotificationPlugin
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * CustomerEmailNotificationPlugin constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * @param \Magento\Customer\Model\EmailNotificationInterface $emailNotification
     * @param callable $proceed
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param string $type
     * @param string $backUrl
     * @param string|int $storeId
     * @param string|null $sendemailStoreId
     *
     * @return void
     */
    public function aroundNewAccount(
        \Magento\Customer\Model\EmailNotificationInterface $emailNotification,
        callable $proceed,
        \Magento\Customer\Api\Data\CustomerInterface $customer,
        $type = \Magento\Customer\Model\EmailNotificationInterface::NEW_ACCOUNT_EMAIL_REGISTERED,
        $backUrl = '',
        $storeId = 0,
        $sendemailStoreId = null
    ) {
        if (!$storeId) {
            $storeId = $this->getWebsiteStoreId($customer, $sendemailStoreId);
        }

        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();

        if (!$this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_DISABLE_CUSTOMER_SUCCESS,
            ScopeInterface::SCOPE_WEBSITES,
            $websiteId
        )
        ) {
            return $proceed($customer, $type, $backUrl, $storeId, $sendemailStoreId);
        }
    }

    /**
     * Get either first store ID from a set website or the provided as default
     * @see \Magento\Customer\Model\EmailNotification
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param int|string|null $defaultStoreId
     * @return int
     */
    private function getWebsiteStoreId($customer, $defaultStoreId = null)
    {
        if ($customer->getWebsiteId() != 0 && empty($defaultStoreId)) {
            $storeIds = $this->storeManager->getWebsite($customer->getWebsiteId())->getStoreIds();
            $defaultStoreId = reset($storeIds);
        }
        return $defaultStoreId;
    }
}
