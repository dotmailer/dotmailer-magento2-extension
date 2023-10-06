<?php

namespace Dotdigitalgroup\Email\Block\EmailCapture;

use Magento\Store\Model\Store;

/**
 * Coupon block
 *
 * @api
 */
class EmailCapture extends \Magento\Framework\View\Element\Template
{
    /**
     * Get email capture url.
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getEmailCaptureUrl()
    {
        /** @var Store $store */
        $store = $this->_storeManager->getStore();
        return $store->getUrl(
            'connector/ajax/emailcapture',
            ['_secure' => $store->isCurrentlySecure()]
        );
    }

    /**
     * Is WBT enabled.
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isWebBehaviourTrackingEnabled()
    {
        $wbt = $this->_scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRACKING_PROFILE_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->_storeManager->getWebsite()->getId()
        );

        return !empty($wbt);
    }
}
