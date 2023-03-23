<?php

namespace Dotdigitalgroup\Email\Block\Customer\Account\Link;

use Dotdigitalgroup\Email\Model\Customer\Account\Configuration;
use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\View\Element\Html\Link\Current;
use Magento\Framework\View\Element\Template\Context;

class NewsletterSubscriptions extends Current
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * @param Context $context
     * @param DefaultPathInterface $defaultPath
     * @param Configuration $config
     */
    public function __construct(
        Context $context,
        DefaultPathInterface $defaultPath,
        Configuration $config
    ) {
        $this->config = $config;
        parent::__construct($context, $defaultPath);
    }

    /**
     * ToHTML method.
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _toHtml()
    {
        $websiteId = $this->_storeManager->getWebsite()->getId();
        if (!$this->config->shouldRedirectToConnectorCustomerIndex($websiteId)) {
            return '';
        }
        return parent::_toHtml();
    }
}
