<?php

namespace Dotdigitalgroup\Email\Block\EmailCapture;

use Magento\Store\Model\Store;

/**
 * Email Capture block
 *
 * @api
 * @deprecated 4.25.0
 * @see \Dotdigitalgroup\Email\Block\EmailCapture
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

    /**
     * Is email capture enabled (applies to checkout only).
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isEasyEmailCaptureEnabled()
    {
        return $this->_scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_EMAIL_CAPTURE,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->_storeManager->getWebsite()->getId()
        );
    }
}
