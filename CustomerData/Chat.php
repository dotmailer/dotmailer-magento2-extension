<?php

namespace Dotdigitalgroup\Email\CustomerData;

use Dotdigitalgroup\Email\Model\Chat\Config;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Dotdigitalgroup\Email\Helper\Data;

class Chat implements SectionSourceInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Data
     */
    private $helper;

    /**
     * Chat constructor.
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Config $config,
        Data $helper,
        StoreManagerInterface $storeManager
    ) {
        $this->config = $config;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
    }

    /**
     * Customer data to add to local storage
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSectionData()
    {
        $this->config->setScopeAndWebsiteId($this->helper->getWebsiteById($this->storeManager->getStore()->getWebsiteId()));
        return [
            'isEnabled' => $this->config->isChatEnabled(),
            'apiSpaceId' => $this->config->getApiSpaceId(),
            'customerId' => $this->getCustomerId(),
            'profileEndpoint' => $this->getEndpointWithStoreCode(),
            'cookieName' => Config::COOKIE_CHAT_PROFILE,
        ];
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getEndpointWithStoreCode()
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB, true)
            . Config::MAGENTO_PROFILE_CALLBACK_ROUTE;
    }

    /**
     * @return string|null
     */
    private function getCustomerId()
    {
        if ($customer = $this->config->getSession()->getQuote()->getCustomer()) {
            return $customer->getId();
        }
        return null;
    }
}
