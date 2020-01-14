<?php

namespace Dotdigitalgroup\Email\Block\EmailCapture;

use Magento\Framework\View\Element\Template\Context;

/**
 * Coupon block
 *
 * @api
 */
class EmailCaptureForCheckout extends EmailCapture
{
    /**
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
