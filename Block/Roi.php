<?php

namespace Dotdigitalgroup\Email\Block;

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
     * @return bool
     */
    public function isRoiTrackingAvailable()
    {
        return $this->helper->isEnabled($this->websiteId) && $this->helper->isRoiTrackingEnabled($this->websiteId);
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    private function getOrder()
    {
        return $this->session->getLastRealOrder();
    }

    /**
     * Get order total
     * @return string
     */
    public function getTotal()
    {
        return number_format($this->getOrder()->getBaseGrandTotal(), 2, '.', ',');
    }

    /**
     * Get product names
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
     * @return string
     */
    public function getPageTrackingUrlForSuccessPage()
    {
        return $this->helper->getPageTrackingUrlForSuccessPage();
    }
}
