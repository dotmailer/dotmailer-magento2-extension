<?php

namespace Dotdigitalgroup\Email\Model;

use Dotdigitalgroup\Email\Helper\Data;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;
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
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var array
     */
    private $integrationMetaData;

    /**
     * @param Data $helper
     * @param ProductMetadataInterface $productMetadata
     * @param ModuleListInterface $moduleList
     */
    public function __construct(
        Data $helper,
        ProductMetadataInterface $productMetadata,
        ModuleListInterface $moduleList
    ) {
        $this->helper = $helper;
        $this->productMetadata = $productMetadata;
        $this->moduleList = $moduleList;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getIntegrationInsightData(): array
    {
        $websiteData = [];
        foreach ($this->helper->getStores(true) as $store) {
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
            ] + $this->getIntegrationMetaData();
            // @codingStandardsIgnoreEnd
        }

        return $websiteData;
    }

    /**
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
            'connectorVersion' => $this->getConnectorVersion(),
            'lastUpdated' => (new \DateTime('now', new \DateTimeZone('UTC')))->format(\DateTime::W3C),
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
