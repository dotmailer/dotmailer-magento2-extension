<?php

namespace Dotdigitalgroup\Email\Block;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;

/**
 * Roi block
 *
 * @api
 */
class Roi extends Template
{

    /**
     * @var Data
     */
    public $helper;

    /**
     * @var Session
     */
    public $session;

    /**
     * @var int
     */
    private $websiteId;

    /**
     * Roi constructor.
     *
     * @param Context $context
     * @param Data $helper
     * @param Session $session
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $helper,
        Session $session,
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
        return number_format(
            (float) $this->getOrder()->getBaseGrandTotal(),
            2,
            '.',
            ','
        );
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
     * @throws LocalizedException
     */
    public function getPageTrackingUrlForSuccessPage()
    {
        $trackingHost = $this->_scopeConfig->getValue(
            Config::TRACKING_HOST,
            ScopeInterface::SCOPE_WEBSITE,
            $this->websiteId
        );

        $version = $this->helper->getTrackingScriptVersionNumber();
        return $this->helper->getTrackingRegionPrefix((int)$this->websiteId) . '.' . $trackingHost . '/_dmmpt'
            . ($version ? '.js?v=' . $version : '');
    }

    /**
     * Get order.
     *
     * @return Order
     */
    private function getOrder()
    {
        return $this->session->getLastRealOrder();
    }
}
