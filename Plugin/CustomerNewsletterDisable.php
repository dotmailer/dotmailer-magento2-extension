<?php

namespace Dotdigitalgroup\Email\Plugin;

/**
 * Customer Newsletter disable susbcriber email depending on settings value.
 */
class CustomerNewsletterDisable
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * CustomerNewsletterDisable constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @param callable $proceed
     * @return mixed
     */
    public function aroundsendNewAccountEmail(\Magento\Customer\Model\Customer $customer, callable $proceed)
    {
        $storeId = $customer->getStoreId();

        if (! $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DISABLE_CUSTOMER_SUCCESS,
            'store',
            $storeId
        )
        ) {
            return $proceed();
        }
    }
}
