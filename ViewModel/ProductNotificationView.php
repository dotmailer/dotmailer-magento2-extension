<?php declare(strict_types=1);

namespace Dotdigitalgroup\Email\ViewModel;

use Dotdigitalgroup\Email\Api\Product\CurrentProductInterface;
use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\CatalogInventory\Model\Configuration;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ProductConfigurableType;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedProductType;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Dotdigitalgroup\Email\Model\Product\VariantDataEncoder;

class ProductNotificationView implements ArgumentInterface
{
    /**
     * Ignored product types
     *
     * A full list of product types can be found in:
     * @ref \Magento\Catalog\Model\Product\Type
     * @ref \Magento\GroupedProduct\Model\Product\Type\Grouped
     */
    private const IGNORED_PRODUCT_TYPES = [
        ProductType::TYPE_BUNDLE,
        GroupedProductType::TYPE_CODE
    ];

    /**
     * @var ScopeConfigInterface
     */
    private $_scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;

    /**
     * @var CurrentProductInterface
     */
    private $currentProduct;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ConfigInterface
     */
    private $productTypesConfig;

    /**
     * @var Visibility
     */
    private $productVisibilities;

    /**
     * @var VariantDataEncoder
     */
    private $variantDataEncoder;

    /**
     * Constructor
     *
     * @param Context $context
     * @param CurrentProductInterface $currentProduct
     * @param Data $helper
     * @param Logger $logger
     * @param ConfigInterface $productTypesConfig
     * @param Visibility $productVisibilities
     * @param VariantDataEncoder $variantDataEncoder
     */
    public function __construct(
        Context $context,
        CurrentProductInterface $currentProduct,
        Data $helper,
        Logger $logger,
        ConfigInterface $productTypesConfig,
        Visibility $productVisibilities,
        VariantDataEncoder $variantDataEncoder
    ) {
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_storeManager = $context->getStoreManager();
        $this->currentProduct = $currentProduct;
        $this->helper = $helper;
        $this->logger = $logger;
        $this->productTypesConfig = $productTypesConfig;
        $this->productVisibilities = $productVisibilities;
        $this->variantDataEncoder = $variantDataEncoder;
    }

    /**
     * Get current product id.
     *
     * @return int|null
     */
    public function getProductId()
    {
        return $this->currentProduct->getProduct()->getId();
    }

    /**
     * Get current product name.
     *
     * @return string
     */
    public function getProductName()
    {
        return $this->currentProduct->getProduct()->getName();
    }

    /**
     * Get current product salable state.
     *
     * @return bool
     */
    public function getProductIsSalable()
    {
        /** @var Product $product */
        $product = $this->currentProduct->getProduct();
        return $product->isSalable();
    }

    /**
     * Return Product back in stock notification ID
     *
     * @return bool|string
     * @throws NoSuchEntityException
     */
    public function getProductNotificationId()
    {
        return $this->_scopeConfig->getValue(
            Config::XML_PATH_BACK_IN_STOCK_NOTIFICATION_ID,
            ScopeInterface::SCOPE_STORE,
            $this->_storeManager->getStore()->getId()
        );
    }

    /**
     * Get out of stock variants.
     *
     * @return false|string
     */
    public function getOutOfStockVariants()
    {
        $displayOutOfStockProducts = $this->_scopeConfig->getValue(
            Configuration::XML_PATH_SHOW_OUT_OF_STOCK
        );

        try {
            if ($displayOutOfStockProducts) {
                return $this->variantDataEncoder->setOutOfStockVariantsForVisibleOutOfStock();
            } else {
                return $this->variantDataEncoder->setOutOfStockVariantsForHiddenOutOfStock();
            }
        } catch (\InvalidArgumentException $e) {
            $this->logger->debug((string) $e);
            return json_encode([]);
        }
    }

    /**
     * Get the ddmbis url.
     *
     * @return string
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getProductNotificationScript():string
    {
        $trackingHost = $this->getProductNotificationScriptHost();
        $params = [
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            'id' => base_convert($this->getDdAccountId(), 10, 36),
            'sid' => $this->getProductNotificationId(),
            't' => '6',
            'bm' => 'true'
        ];

        return '//' . $this->helper->getRegionPrefix() . $trackingHost . '/ddmbis.js?' .
            http_build_query($params);
    }

    /**
     * Can inject and display the back in stock script.
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function canDisplay(): bool
    {
        return $this->isProductVisibilityAllowed()
            && $this->isProductTypeAllowed()
            && !empty($this->getProductNotificationId());
    }

    /**
     * Is product visible
     *
     * Returns value to determine if the product is allowed
     * to be included in the back in stock notification.
     *
     * @return bool
     */
    private function isProductVisibilityAllowed(): bool
    {
        try {
            $productVisibility = $this->currentProduct->getProductVisibility();
            $allowedProductVisibilities = $this->getConfigurationVisibility();
        } catch (NoSuchEntityException $e) {
            $this->logger->debug((string)$e);
            return false;
        }

        try {
            $productType = $this->currentProduct->getProductType();
            if ($productType === ProductConfigurableType::TYPE_CODE) {
                if (!in_array(
                    Visibility::VISIBILITY_NOT_VISIBLE,
                    $allowedProductVisibilities,
                    true
                )) {
                    return false;
                }
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->debug((string)$e);
            return false;
        }

        return in_array(
            $productVisibility,
            $allowedProductVisibilities,
            true
        );
    }

    /**
     * Product type allowed
     *
     * Is the product type allowed for back in
     * stock notification scrip based on the configuration settings
     *
     * @return bool
     */
    private function isProductTypeAllowed(): bool
    {
        try {
            $productType = $this->currentProduct->getProductType();
            $allowedProductTypes = $this->getConfigurationAllowedTypes();
        } catch (NoSuchEntityException $e) {
            $this->logger->debug((string)$e);
            return false;
        }

        if ($productType === ProductConfigurableType::TYPE_CODE) {
            if (!in_array(
                ProductType::TYPE_SIMPLE,
                $allowedProductTypes,
                true
            )) {
                return false;
            }
        }

        return in_array(
            $productType,
            $allowedProductTypes,
            true
        );
    }

    /**
     * Dotdigital catalog visibility settings
     *
     * @return array
     * @throws NoSuchEntityException
     */
    private function getConfigurationVisibility(): array
    {
        $visibility = explode(
            ',',
            $this->_scopeConfig->getValue(
                Config::XML_PATH_CONNECTOR_SYNC_CATALOG_VISIBILITY,
                ScopeInterface::SCOPE_WEBSITE,
                $this->_storeManager->getWebsite()->getId()
            )
        ) ?? [];

        if (in_array(0, $visibility)) {
            $visibility = array_merge(
                $visibility,
                array_keys($this->productVisibilities::getOptionArray())
            );
        }

        return array_map(
            function ($configurationValue) {
                return (int)$configurationValue;
            },
            $visibility
        );
    }

    /**
     * Dotdigital catalog allowed product types
     *
     * @return array
     * @throws NoSuchEntityException
     */
    private function getConfigurationAllowedTypes(): array
    {
        $allowedTypesString = $this->_scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_SYNC_CATALOG_TYPE,
            ScopeInterface::SCOPE_WEBSITE,
            $this->_storeManager->getWebsite()->getId()
        );

        $allowedTypesList = array_map(
            function ($configurationValue) {
                return (string)$configurationValue;
            },
            explode(',', $allowedTypesString ?? [])
        );

        if (in_array('0', $allowedTypesList)) {
            $allowedTypesList = array_unique(
                array_merge(
                    $allowedTypesList,
                    array_column($this->productTypesConfig->getAll(), 'name')
                )
            );
        }

        return array_diff($allowedTypesList, self::IGNORED_PRODUCT_TYPES);
    }

    /**
     * Get product notification script host
     *
     * @return bool|string
     * @throws LocalizedException
     */
    private function getProductNotificationScriptHost()
    {
        return $this->_scopeConfig->getValue(
            Config::TRACKING_HOST,
            ScopeInterface::SCOPE_WEBSITE,
            $this->_storeManager->getWebsite()->getId()
        );
    }

    /**
     * Return Product back in stock notification ID
     *
     * @return bool|string
     * @throws NoSuchEntityException
     */
    private function getDdAccountId()
    {
        return $this->_scopeConfig->getValue(
            Config::PATH_FOR_ACCOUNT_ID,
            ScopeInterface::SCOPE_STORE,
            $this->_storeManager->getStore()->getId()
        );
    }
}
