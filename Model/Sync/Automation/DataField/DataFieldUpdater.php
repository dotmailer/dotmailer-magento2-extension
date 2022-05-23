<?php

namespace Dotdigitalgroup\Email\Model\Sync\Automation\DataField;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;

class DataFieldUpdater
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
     * @var Website
     */
    protected $website;

    /**
     * @var string
     */
    protected $email;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * DataFieldUpdater constructor.
     * @param Data $helper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Data $helper,
        StoreManagerInterface $storeManager
    ) {
        $this->helper = $helper;
        $this->storeManager = $storeManager;
    }

    /**
     * Set default data fields.
     *
     * @param string $email
     * @param string|int $websiteId
     * @param string $storeName
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setDefaultDataFields($email, $websiteId, $storeName)
    {
        /** @var Website $website */
        $website = $this->getWebsite($websiteId);
        $this->email = $email;

        if ($storeNameKey = $website->getConfig(
            Config::XML_PATH_CONNECTOR_CUSTOMER_STORE_NAME
        )
        ) {
            $this->data[] = [
                'Key' => $storeNameKey,
                'Value' => $storeName,
            ];
        }
        if ($websiteName = $website->getConfig(
            Config::XML_PATH_CONNECTOR_CUSTOMER_WEBSITE_NAME
        )
        ) {
            $this->data[] = [
                'Key' => $websiteName,
                'Value' => $website->getName(),
            ];
        }

        return $this;
    }

    /**
     * Update data fields.
     *
     * @return void
     * @throws \Exception
     */
    public function updateDataFields()
    {
        if (!empty($this->getData())) {
            $client = $this->helper->getWebsiteApiClient($this->getWebsite()->getId());
            $client->updateContactDatafieldsByEmail(
                $this->email,
                $this->getData()
            );
        }
    }

    /**
     * Get data.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get website from storeManager or cache.
     *
     * @param string|int $websiteId
     * @return \Magento\Store\Api\Data\WebsiteInterface|Website
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getWebsite($websiteId = 0)
    {
        if (!isset($this->website)) {
            $this->website = $this->storeManager->getWebsite($websiteId);
        }
        return $this->website;
    }
}
