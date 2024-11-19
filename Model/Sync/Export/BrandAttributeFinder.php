<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Export;

use Dotdigitalgroup\Email\Helper\Config as ConfigHelper;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;

class BrandAttributeFinder
{
    /**
     * @var ProductResource
     */
    private $productResource;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ProductResource $productResource
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ProductResource $productResource,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->productResource = $productResource;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get brand attribute.
     *
     * @param string|int $websiteId
     *
     * @return AbstractAttribute|false
     */
    public function getBrandAttribute($websiteId)
    {
        $attributeCode = $this->scopeConfig->getValue(
            ConfigHelper::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_BRAND_ATTRIBUTE,
            ScopeInterface::SCOPE_WEBSITES,
            $websiteId
        );

        try {
            return $this->productResource->getAttribute($attributeCode);
        } catch (LocalizedException $e) {
            return false;
        }
    }

    /**
     * Get brand attribute code by store id.
     *
     * @param int $storeId
     *
     * @return string
     */
    public function getBrandAttributeCodeByStoreId(int $storeId)
    {
        return $this->scopeConfig->getValue(
            ConfigHelper::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_BRAND_ATTRIBUTE,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }
}
