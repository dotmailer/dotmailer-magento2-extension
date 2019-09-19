<?php

namespace Dotdigitalgroup\Email\Model;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class IntegrationInsightData
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var array
     */
    private $systemMetadata;

    /**
     * @param Data $helper
     * @param ProductMetadataInterface $productMetadata
     * @param ModuleListInterface $moduleList
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        Data $helper,
        ProductMetadataInterface $productMetadata,
        ModuleListInterface $moduleList,
        TimezoneInterface $timezone
    ) {
        $this->helper = $helper;
        $this->productMetadata = $productMetadata;
        $this->moduleList = $moduleList;
        $this->timezone = $timezone;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getIntegrationInsightData(): array
    {
        $websiteData = $this->getData();

        return array_map(
            function ($websiteData, $apiUsername) {
                $apiHash = str_replace('apiuser-', '', strtok($apiUsername, '@'));
                return [
                    'recordId' => sprintf('integration_%s', $apiHash),
                    'websites' => $websiteData,
                    'apiUsername' => $apiUsername,
                    'lastUpdated' => $this->timezone->date()->format(\DateTime::W3C),
                ] + $this->getSystemMetadata();
            },
            $websiteData,
            array_keys($websiteData)
        );
    }

    /**
     * Compile website data by API username
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getData(): array
    {
        $websiteData = [];
        foreach ($this->helper->getStores() as $store) {
            /** @var Store $store */
            if (!$this->helper->isEnabled($store->getWebsiteId())) {
                continue;
            }

            $websiteData[$this->helper->getApiUsername($store->getWebsiteId())][] = [
                'id' => $store->getWebsiteId(),
                'baseUrl' => $store->getBaseUrl(UrlInterface::URL_TYPE_LINK, $store->isCurrentlySecure()),
                'name' => $store->getWebsite()->getName(),
            ];
        }

        return $websiteData;
    }

    /**
     * @return array
     */
    private function getSystemMetadata(): array
    {
        if ($this->systemMetadata) {
            return $this->systemMetadata;
        }

        return $this->systemMetadata = [
            'platform' => $this->productMetadata->getName(),
            'edition' => $this->productMetadata->getEdition(),
            'version' => $this->productMetadata->getVersion(),
            'connectorVersion' => $this->getConnectorVersion(),
        ];
    }

    /**
     * @return string
     */
    private function getConnectorVersion(): string
    {
        return $this->moduleList->getOne('Dotdigitalgroup_Email')['setup_version'];
    }
}
