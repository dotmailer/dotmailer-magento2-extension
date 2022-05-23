<?php

namespace Dotdigitalgroup\Email\Block;

use Dotdigitalgroup\Email\Helper\Config;
use Magento\Store\Model\ScopeInterface;

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
     * Page tracking available.
     *
     * Checks if Extension and Page Tracking tracking is enabled.
     *
     * @return bool
     */
    public function isPageTrackingAvailable()
    {
        return $this->isNotDisplayingRoiSpecificScript() && $this->isApiAndPageTrackingEnabled();
    }

    /**
     * Page tracking url.
     *
     * @return string
     */
    public function getPageTrackingUrl(): string
    {
        $trackingHost = $this->_scopeConfig->getValue(
            Config::TRACKING_HOST,
            ScopeInterface::SCOPE_WEBSITE,
            $this->websiteId
        );

        $version = $this->helper->getTrackingScriptVersionNumber();
        return '//' . $this->helper->getRegionPrefix() . $trackingHost . '/_dmpt'
            . ($version ? '.js?v=' . $version : '');
    }

    /**
     * Not display roi script.
     *
     * Roi script needs to be displayed only in checkout page.
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function isNotDisplayingRoiSpecificScript()
    {
        return $this->isNotInCheckoutPage() || ! $this->helper->isRoiTrackingEnabled($this->websiteId);
    }

    /**
     * Api and page tracking enabled.
     *
     * Checks if is API and Page Tracking Enabled.
     *
     * @return bool
     */
    private function isApiAndPageTrackingEnabled()
    {
        return $this->helper->isEnabled($this->websiteId) && $this->helper->isPageTrackingEnabled($this->websiteId);
    }

    /**
     * Is not in checkout page.
     *
     * Checks if the user is in checkout page.
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function isNotInCheckoutPage()
    {
        return ! in_array('checkout_onepage_success', $this->getLayout()->getUpdate()->getHandles());
    }
}
