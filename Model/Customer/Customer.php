<?php

namespace Dotdigitalgroup\Email\Model\Customer;

class Customer extends \Magento\Customer\Model\Customer
{
    /**
     * Send email with new account related information
     *
     * @param string $type
     * @param string $backUrl
     * @param string $storeId
     * @return \Magento\Customer\Model\Customer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sendNewAccountEmail($type = 'registered', $backUrl = '', $storeId = '0')
    {
        if (! $storeId) {
            $storeId = $this->_getWebsiteStoreId($this->getSendemailStoreId());
        }

        if ($this->_scopeConfig->isSetFlag(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DISABLE_CUSTOMER_SUCCESS,
            'store',
            $storeId
        )
        ) {
            return $this;
        } else {
            return parent::sendNewAccountEmail($type, $backUrl, $storeId);
        }
    }
}
