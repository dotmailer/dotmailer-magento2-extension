<?php

namespace Dotdigitalgroup\Email\Plugin;

use Dotdigitalgroup\Email\Helper\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;

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
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Around new account.
     *
     * @param \Magento\Customer\Model\EmailNotificationInterface $emailNotification
     * @param callable $proceed
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param string $type
     * @param string $backUrl
     * @param int $storeId
     * @param ?int $sendemailStoreId
     * @return void
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
     * Get website store id.
     *
     * Get either first store ID from a set website or the provided as default
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param int|string|null $defaultStoreId
     * @return int
     * @throws LocalizedException
     * @see \Magento\Customer\Model\EmailNotification
     */
    private function getWebsiteStoreId($customer, $defaultStoreId = null)
    {
        if ($customer->getWebsiteId() != 0 && empty($defaultStoreId)) {
            /** @var Website $website */
            $website = $this->storeManager->getWebsite($customer->getWebsiteId());
            $storeIds = $website->getStoreIds();
            $defaultStoreId = reset($storeIds);
        }
        return $defaultStoreId;
    }
}
