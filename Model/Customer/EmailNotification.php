<?php

namespace Dotdigitalgroup\Email\Model\Customer;

class EmailNotification extends \Magento\Customer\Model\EmailNotification
{
    private $scopeConfig;
    /**
     * EmailNotification constructor.
     * @param \Magento\Customer\Model\CustomerRegistry $customerRegistry
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Customer\Helper\View $customerViewHelper
     * @param \Magento\Framework\Reflection\DataObjectProcessor $dataProcessor
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Customer\Model\CustomerRegistry $customerRegistry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Customer\Helper\View $customerViewHelper,
        \Magento\Framework\Reflection\DataObjectProcessor $dataProcessor,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($customerRegistry, $storeManager, $transportBuilder, $customerViewHelper,
            $dataProcessor, $scopeConfig);
    }

    /**
     * Send email with new account related information
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param string $type
     * @param string $backUrl
     * @param int $storeId
     * @param null $sendemailStoreId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function newAccount(
        \Magento\Customer\Api\Data\CustomerInterface $customer,
        $type = self::NEW_ACCOUNT_EMAIL_REGISTERED,
        $backUrl = '',
        $storeId = 0,
        $sendemailStoreId = null
    ) {
        if (!$this->scopeConfig->isSetFlag(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DISABLE_CUSTOMER_SUCCESS,
            'store', $storeId)
        ) {
            parent::newAccount($customer, $type, $backUrl, $storeId, $sendemailStoreId);
        }
    }
}
