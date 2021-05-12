<?php

namespace Dotdigitalgroup\Email\Model\Frontend;

use Dotdigitalgroup\Email\Helper\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class PwaUrlConfig
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var array
     */
    private $scopedPwaUrls;

    /**
     * PwaUrlConfig constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param string|int $websiteId
     */
    private function setPwaUrl($websiteId)
    {
        $this->scopedPwaUrls[$websiteId] = $this->scopeConfig->getValue(
            Config::XML_PATH_PWA_URL,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * @param string|int $websiteId
     * @return string
     */
    public function getPwaUrl($websiteId)
    {
        if (!isset($this->scopedPwaUrls[$websiteId])) {
            $this->setPwaUrl($websiteId);
        }

        return $this->scopedPwaUrls[$websiteId];
    }
}
