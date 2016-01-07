<?php

namespace Dotdigitalgroup\Email\Block;

class Roi  extends \Magento\Framework\View\Element\Template
{
    protected $helper;
    protected $session;

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

    public function getRoiTrackingEnabled()
    {
        return $this->helper->getRoiTrackingEnabled();
    }

    public function getOrder()
    {
        return $this->session->getLastRealOrder();
    }
}