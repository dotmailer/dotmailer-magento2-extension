<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector\V3;

use Dotdigital\V3\Client as DotdigitalClient;
use Dotdigital\Resources\AbstractResource;
use Dotdigitalgroup\Email\Helper\Data;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

class Client
{
    /**
     * @var DotdigitalClient
     */
    private $sdk;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Data $helper
     * @param StoreManagerInterface $storeManager
     * @param DotdigitalClient $sdk
     * @throws LocalizedException
     */
    public function __construct(
        Data $helper,
        StoreManagerInterface $storeManager,
        DotdigitalClient $sdk
    ) {
        $this->sdk = $sdk;
        $this->storeManager = $storeManager;
        $this->helper = $helper;
        $this->setApiClientContext($this->storeManager->getWebsite()->getId());
    }

    /**
     * Prepare client for website context request.
     *
     * @param int $websiteId
     * @return $this
     */
    public function setApiClientContext(int $websiteId): self
    {
        $this->sdk->setApiUser((string) $this->helper->getApiUsername($websiteId));
        $this->sdk->setApiPassword((string) $this->helper->getApiPassword($websiteId));
        $this->sdk->setApiEndpoint((string) $this->helper->getApiEndPointFromConfig($websiteId));
        return $this;
    }

    /**
     * Get client resource from dotdigital client.
     *
     * @param string $name
     * @return AbstractResource
     */
    public function __get(string $name): AbstractResource
    {
        return $this->sdk->__get($name);
    }
}
