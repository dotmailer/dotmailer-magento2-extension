<?php

namespace Dotdigitalgroup\Email\Block;

/**
 * Tracking block
 *
 * @api
 */
class Tracking extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var int
     */
    private $websiteId;

    /**
     * Tracking constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);

        $this->websiteId = $this->_storeManager->getWebsite()->getId();
    }

    /**
     * Checks if Extension and Page Tracking tracking is enabled
     * @return bool
     */
    public function isPageTrackingAvailable()
    {
        return $this->isNotDisplayingRoiSpecificScript() && $this->isApiAndPageTrackingEnabled();
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function isNotDisplayingRoiSpecificScript()
    {
        return $this->isNotInCheckoutPage() || ! $this->helper->isRoiTrackingEnabled($this->websiteId);
    }

    /**
     * Checks if is API and Page Tracking Enabled
     * @return bool
     */
    private function isApiAndPageTrackingEnabled()
    {
        return $this->helper->isEnabled($this->websiteId) && $this->helper->isPageTrackingEnabled($this->websiteId);
    }

    /**
     * Checks if the user is on Checkout Page or No
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function isNotInCheckoutPage()
    {
        return ! in_array('checkout_onepage_success', $this->getLayout()->getUpdate()->getHandles());
    }

    /**
     * @return string
     */
    public function getPageTrackingUrl()
    {
        return $this->helper->getPageTrackingUrl();
    }
}
