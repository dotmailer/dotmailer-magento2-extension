<?php

namespace Dotdigitalgroup\Email\Block;

use Dotdigitalgroup\Email\Helper\Config;

/**
 * Roi block
 *
 * @api
 */
class Roi extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    public $session;

    /**
     * @var int
     */
    private $websiteId;

    /**
     * Roi constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Checkout\Model\Session $session
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Checkout\Model\Session $session,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->session = $session;
        parent::__construct($context, $data);

        $this->websiteId = $this->_storeManager->getWebsite()->getId();
    }

    /**
     * Is roi available.
     *
     * @return bool
     */
    public function isRoiTrackingAvailable()
    {
        return $this->helper->isEnabled($this->websiteId) && $this->helper->isRoiTrackingEnabled($this->websiteId);
    }

    /**
     * Get order total.
     *
     * @return string
     */
    public function getTotal()
    {
        return number_format((float) $this->getOrder()->getBaseGrandTotal(), 2, '.', ',');
    }

    /**
     * Get product names.
     *
     * @return string
     */
    public function getProductNames()
    {
        $items = $this->getOrder()->getAllItems();
        $productNames = [];
        foreach ($items as $item) {
            if ($item->getParentItemId() === null) {
                $productNames[] = str_replace('"', ' ', $item->getName());
            }
        }
        return json_encode($productNames);
    }

    /**
     * Tracking Url.
     *
     * @return string
     */
    public function getPageTrackingUrlForSuccessPage()
    {
        $trackingHost = $this->_scopeConfig->getValue(
            Config::TRACKING_HOST,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->websiteId
        );

        $version = $this->helper->getTrackingScriptVersionNumber();
        return '//' . $this->helper->getRegionPrefix() . $trackingHost . '/_dmmpt'
            . ($version ? '.js?v=' . $version : '');
    }

    /**
     * Get order.
     *
     * @return \Magento\Sales\Model\Order
     */
    private function getOrder()
    {
        return $this->session->getLastRealOrder();
    }
}
