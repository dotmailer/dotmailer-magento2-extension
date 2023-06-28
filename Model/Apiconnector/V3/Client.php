<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector\V3;

use Dotdigital\V3\Client as DotdigitalClient;
use Dotdigital\V3\ClientFactory as DotdigitalClientFactory;
use Dotdigital\Resources\AbstractResource;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\ClientInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @mixin DotdigitalClient
 */
class Client extends DataObject implements ClientInterface
{
    /**
     * @var DotdigitalClient
     */
    private $sdk;

    /**
     * @var DotdigitalClientFactory
     */
    private $sdkFactory;

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
     * @param DotdigitalClientFactory $sdkFactory
     * @param array $data
     *
     * @throws LocalizedException
     */
    public function __construct(
        Data $helper,
        StoreManagerInterface $storeManager,
        DotdigitalClientFactory $sdkFactory,
        array $data = []
    ) {
        $this->sdkFactory = $sdkFactory;
        $this->storeManager = $storeManager;
        $this->helper = $helper;

        parent::__construct($data);

        $this->setApiClientContext($this->getWebsiteId() ?: $this->storeManager->getWebsite()->getId());
    }

    /**
     * Prepare client for website context request.
     *
     * @param int $websiteId
     * @return $this
     */
    public function setApiClientContext(int $websiteId): self
    {
        $this->sdk = $this->sdkFactory->create();
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

    /**
     * Get website id.
     *
     * @return string|int|null
     */
    private function getWebsiteId()
    {
        return $this->_getData('websiteId');
    }
}
