<?php

namespace Dotdigitalgroup\Email\Plugin;

/**
 * Disable customer email depending on settings value.
 */
class CustomerPlugin
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * CustomerPlugin constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @param callable $proceed
     * @param string $type
     * @param string $backUrl
     * @param string $storeId
     *
     * @return mixed
     */
    public function aroundSendNewAccountEmail(
        \Magento\Customer\Model\Customer $customer,
        callable $proceed,
        $type = 'registered',
        $backUrl = '',
        $storeId = '0'
    ) {
        $storeId = $customer->getStoreId();

        if (! $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DISABLE_CUSTOMER_SUCCESS,
            'store',
            $storeId
        )
        ) {
            return $proceed($type, $backUrl, $storeId);
        }
    }
}
