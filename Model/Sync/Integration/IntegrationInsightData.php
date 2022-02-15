<?php

namespace Dotdigitalgroup\Email\Model\Sync\Integration;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Connector\Module;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;

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
     * @var array
     */
    private $integrationMetaData;

    /**
     * @var DotdigitalConfig
     */
    private $dotdigitalConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Module
     */
    private $moduleManager;

    /**
     * IntegrationInsightData constructor.
     *
     * @param Data $helper
     * @param ProductMetadataInterface $productMetadata
     * @param DotdigitalConfig $dotdigitalConfig
     * @param StoreManagerInterface $storeManager
     * @param Module $moduleManager
     */
    public function __construct(
        Data $helper,
        ProductMetadataInterface $productMetadata,
        DotdigitalConfig $dotdigitalConfig,
        StoreManagerInterface $storeManager,
        Module $moduleManager
    ) {
        $this->helper = $helper;
        $this->productMetadata = $productMetadata;
        $this->dotdigitalConfig = $dotdigitalConfig;
        $this->storeManager = $storeManager;
        $this->moduleManager = $moduleManager;
    }

    /**
     * Fetch insight data for all websites.
     *
     * @return array
     * @throws \Exception
     */
    public function getIntegrationInsightData(): array
    {
        $websiteData = [];
        foreach ($this->storeManager->getStores() as $store) {
            /** @var Store $store */
            if (!$this->helper->isEnabled($store->getWebsiteId()) || isset($websiteData[$store->getWebsiteId()])) {
                continue;
            }

            // @codingStandardsIgnoreStart
            $websiteData[$store->getWebsiteId()] = [
                'recordId' => parse_url(
                    $store->getBaseUrl(UrlInterface::URL_TYPE_LINK, $store->isCurrentlySecure()),
                    PHP_URL_HOST
                ),
            ] + $this->getIntegrationMetaData() + ['configuration' => $this->getConfiguration($store->getWebsiteId())];
            // @codingStandardsIgnoreEnd
        }

        return $websiteData;
    }

    /**
     * Get system information.
     *
     * @return array
     * @throws \Exception
     */
    private function getIntegrationMetaData(): array
    {
        if ($this->integrationMetaData) {
            return $this->integrationMetaData;
        }

        return $this->integrationMetaData = [
            'platform' => $this->productMetadata->getName(),
            'edition' => $this->productMetadata->getEdition(),
            'version' => $this->productMetadata->getVersion(),
            'phpVersion' => implode('.', [PHP_MAJOR_VERSION, PHP_MINOR_VERSION, PHP_RELEASE_VERSION]),
            'connectorVersion' => $this->moduleManager->getModuleVersion(Module::MODULE_NAME),
            'lastUpdated' => (new \DateTime('now', new \DateTimeZone('UTC')))->format(\DateTime::W3C)
        ];
    }

    /**
     * Get connector configurations.
     *
     * @param string|int $websiteId
     * @return array
     */
    private function getConfiguration($websiteId)
    {
        return $this->dotdigitalConfig->getConfig($websiteId);
    }
}
