<?php

namespace Dotdigitalgroup\Email\Block;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Tracking\EndpointResolver;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;

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
     * @var EndpointResolver
     */
    private $endpointResolver;

    /**
     * Roi constructor.
     *
     * @param Context $context
     * @param Data $helper
     * @param Session $session
     * @param EndpointResolver $endpointResolver
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $helper,
        Session $session,
        EndpointResolver $endpointResolver,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->session = $session;
        $this->endpointResolver = $endpointResolver;
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
        $trackingUrl = $this->endpointResolver->getUrl((int)$this->websiteId);

        $version = $this->helper->getTrackingScriptVersionNumber();
        return $trackingUrl . '/_dmmpt'
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
