<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Chat\Trial;

use Magento\Framework\View\Element\Template;
use Magento\Framework\UrlInterface;
use Dotdigitalgroup\Email\Model\Chat\Config;

class Render extends Template
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Render constructor.
     *
     * @param Template\Context $context
     * @param Config $config
     * @param array $data
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        Template\Context $context,
        Config $config,
        UrlInterface $urlBuilder,
        array $data = []
    ) {
        $this->config = $config;
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    public function isApiEnabled()
    {
        return $this->config->isChatEnabled();
    }
}
