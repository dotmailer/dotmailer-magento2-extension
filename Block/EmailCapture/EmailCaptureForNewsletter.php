<?php

namespace Dotdigitalgroup\Email\Block\EmailCapture;

use Magento\Framework\View\Element\Template\Context;

/**
 * Coupon block
 *
 * @api
 */
class EmailCaptureForNewsletter extends EmailCapture
{
    /**
     * EmailCapture constructor.
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isEasyEmailCaptureForNewsletterEnabled()
    {
        return $this->_scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_EMAIL_CAPTURE_NEWSLETTER,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->_storeManager->getWebsite()->getId()
        );
    }
}
