<?php

namespace Dotdigitalgroup\Email\Block\EmailCapture;

use Magento\Framework\View\Element\Template\Context;

/**
 * Coupon block
 *
 * @api
 */
class EmailCapture extends \Magento\Framework\View\Element\Template
{
    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getEmailCaptureUrl()
    {
        return $this->_storeManager->getStore()->getUrl(
            'connector/ajax/emailcapture',
            ['_secure' => $this->_storeManager->getStore()->isCurrentlySecure()]
        );
    }

    /**
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
