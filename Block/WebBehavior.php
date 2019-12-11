<?php

namespace Dotdigitalgroup\Email\Block;

use Dotdigitalgroup\Email\Helper\Data;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Tracking block
 *
 * @api
 */
class WebBehavior extends Template
{

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * WebBehavior constructor.
     * @param Context $context
     * @param Data $helper
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $helper,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    /**
     * @return bool|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProfileId()
    {
        return $this->helper->getProfileId($this->storeManager->getStore()->getWebsiteId());
    }
}
