<?php

namespace Dotdigitalgroup\Email\Block;

class Roi extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $helper;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $session;

    /**
     * Roi constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data               $helper
     * @param \Magento\Checkout\Model\Session                  $session
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array                                            $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Checkout\Model\Session $session,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->session = $session;
        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    public function getRoiTrackingEnabled()
    {
        return $this->helper->getRoiTrackingEnabled();
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->session->getLastRealOrder();
    }
}
