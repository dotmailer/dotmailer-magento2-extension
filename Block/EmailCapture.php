<?php

namespace Dotdigitalgroup\Email\Block;

use Dotdigitalgroup\Email\Helper\Config;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

/**
 * Email Capture block
 *
 * @deprecated since 4.33.0
 * @see \Dotdigitalgroup\Sms\ViewModel\DotdigitalEmailCaptureView
 */
class EmailCapture extends Template
{
    /**
     * Get email capture url.
     *
     * @return string
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
            Config::XML_PATH_CONNECTOR_TRACKING_PROFILE_ID,
            ScopeInterface::SCOPE_WEBSITE,
            $this->_storeManager->getWebsite()->getId()
        );

        return !empty($wbt);
    }

    /**
     * Is email capture enabled (applies to checkout only).
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isEasyEmailCaptureEnabled()
    {
        return $this->_scopeConfig->isSetFlag(
            Config::XML_PATH_CONNECTOR_EMAIL_CAPTURE,
            ScopeInterface::SCOPE_WEBSITE,
            $this->_storeManager->getWebsite()->getId()
        );
    }
}
