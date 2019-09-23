<?php

namespace Dotdigitalgroup\Email\Model\Connector;

use Dotdigitalgroup\Email\Model\Product\AttributeFactory;

/**
 * Transactional data for catalog products to sync.
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Product
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var string
     */
    public $sku = '';

    /**
     * @var string
     */
    public $status = '';

    /**
     * @var string
     */
    public $visibility = '';

    /**
     * @var float
     */
    public $price = 0;

    /**
     * @var float
     */
    public $specialPrice = 0;

    /**
     * @var array
     */
    public $categories = [];

    /**
     * @var string
     */
    public $url = '';

    /**
     * @var string
     */
    public $imagePath = '';

    /**
     * @var string
     */
    public $shortDescription = '';

    /**
     * @var float
     */
    public $stock = 0;

    /**
     * @var array
     */
    public $websites = [];

    /**
     * @var string
     */
    public $type = '';

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Catalog\Model\Product\Attribute\Source\StatusFactory
     */
    public $statusFactory;

    /**
     * @var \Magento\Catalog\Model\Product\VisibilityFactory
     */
    public $visibilityFactory;

    /**
     * @var \Magento\CatalogInventory\Model\Stock\ItemFactory
     */
    public $itemFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\Catalog\UrlFinder
     */
    private $urlFinder;

    /**
     * @var \Magento\CatalogInventory\Api\StockStateInterface
     */
    private $stockStateInterface;

    /**
     * @var AttributeFactory $attributeHandler
     */
    private $attributeHandler;

    /**
     * Product constructor.
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Catalog\Model\Product\Media\ConfigFactory $mediaConfigFactory
     * @param \Magento\Catalog\Model\Product\Attribute\Source\StatusFactory $statusFactory
     * @param \Magento\Catalog\Model\Product\VisibilityFactory $visibilityFactory
     * @param \Dotdigitalgroup\Email\Model\Catalog\UrlFinder $urlFinder
     * @param \Magento\CatalogInventory\Api\StockStateInterface $stockStateInterface
     * @param AttributeFactory $attributeHandler
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Catalog\Model\Product\Attribute\Source\StatusFactory $statusFactory,
        \Magento\Catalog\Model\Product\VisibilityFactory $visibilityFactory,
        \Dotdigitalgroup\Email\Model\Catalog\UrlFinder $urlFinder,
        \Magento\CatalogInventory\Api\StockStateInterface $stockStateInterface,
        AttributeFactory $attributeHandler
    ) {
        $this->visibilityFactory  = $visibilityFactory;
        $this->statusFactory      = $statusFactory;
        $this->helper             = $helper;
        $this->storeManager       = $storeManagerInterface;
        $this->urlFinder          = $urlFinder;
        $this->stockStateInterface = $stockStateInterface;
        $this->attributeHandler = $attributeHandler;
    }

    /**
     * Set the product data.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param string $storeId
     *
     * @return $this
     */
    public function setProduct($product, $storeId)
    {
        $this->id = $product->getId();
        $this->sku = $product->getSku();
        $this->name = $product->getName();

        $this->status = $this->statusFactory->create()
            ->getOptionText($product->getStatus());

        $this->type = ucfirst($product->getTypeId());

        $options = $this->visibilityFactory->create()
            ->getOptionArray();
        $this->visibility = (string)$options[$product->getVisibility()];

        $this->getMinPrices($product);

        $this->url = $this->urlFinder->fetchFor($product);

        $this->imagePath = $this->urlFinder->getProductSmallImageUrl($product);

        $this->stock = (float)number_format($this->getStockQty($product), 2, '.', '');

        //limit short description
        $this->shortDescription = mb_substr(
            $product->getShortDescription(),
            0,
            \Dotdigitalgroup\Email\Helper\Data::DM_FIELD_LIMIT
        );

        //category data
        $count = 0;
        $categoryCollection = $product->getCategoryCollection()
            ->addNameToResult();
        foreach ($categoryCollection as $cat) {
            $this->categories[$count]['Id'] = $cat->getId();
            $this->categories[$count]['Name'] = $cat->getName();
            ++$count;
        }

        //website data
        $count = 0;
        $websiteIds = $product->getWebsiteIds();
        foreach ($websiteIds as $websiteId) {
            $website = $this->storeManager->getWebsite(
                $websiteId
            );
            $this->websites[$count]['Id'] = $website->getId();
            $this->websites[$count]['Name'] = $website->getName();
            ++$count;
        }

        $this->processProductOptions($product, $storeId);

        unset(
            $this->itemFactory,
            $this->visibilityFactory,
            $this->statusFactory,
            $this->helper,
            $this->storeManager,
            $this->attributeHandler
        );

        return $this;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * This function calculates the stock Quantity for each Product.
     * @return float
     */
    private function getStockQty($product)
    {
        return $this->stockStateInterface->getStockQty($product->getId(), $product->getStore()->getWebsiteId());
    }

    /**
     * Retrieve product attributes for catalog sync.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param string $storeId
     *
     * @return null
     */
    private function processProductOptions($product, $storeId)
    {
        $attributeModel = $this->attributeHandler->create();

        $attributeSetKey = 'attribute_set';
        $this->$attributeSetKey = $attributeModel->getAttributeSetName($product);

        //selected attributes from config
        $configAttributes = $attributeModel->getConfigAttributesForSync(
            $this->storeManager->getStore($storeId)->getWebsiteId()
        );

        if ($configAttributes) {
            $configAttributes = explode(',', $configAttributes);
            //attributes from attribute set
            $attributesFromAttributeSet = $attributeModel->getAttributesArray(
                $product->getAttributeSetId()
            );

            $attributes = $attributeModel->processConfigAttributes(
                $configAttributes,
                $attributesFromAttributeSet,
                $product
            );

            if ($attributes->hasValues()) {
                $attributesKey = 'attributes';
                $this->$attributesKey = $attributes;
            }
        }
    }

    /**
     * Exposes the class as an array of objects.
     *
     * @return array
     */
    public function expose()
    {
        return array_diff_key(
            get_object_vars($this),
            array_flip([
                'storeManager',
                'helper',
                'itemFactory',
                'mediaConfigFactory',
                'visibilityFactory',
                'statusFactory',
                'storeManager',
                'urlFinder',
                'stockStateInterface',
                'attributeHandler'
            ])
        );
    }

    /**
     * Set the Minimum Prices for Configurable and Bundle products.
     *
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return null
     */

    private function getMinPrices($product)
    {
        if ($product->getTypeId() == 'configurable') {
            foreach ($product->getTypeInstance()->getUsedProducts($product) as $childProduct) {
                $childPrices[] = $childProduct->getPrice();
                if ($childProduct->getSpecialPrice() !== null) {
                    $childSpecialPrices[] = $childProduct->getSpecialPrice();
                }
            }
            $this->price = isset($childPrices) ? min($childPrices) : null;
            $this->specialPrice = isset($childSpecialPrices) ? min($childSpecialPrices) : null;
        } elseif ($product->getTypeId() == 'bundle') {
            $this->price = $product->getPriceInfo()->getPrice('regular_price')->getMinimalPrice()->getValue();
            $this->specialPrice = $product->getPriceInfo()->getPrice('final_price')->getMinimalPrice()->getValue();
            //if special price equals to price then its wrong.)
            $this->specialPrice = ($this->specialPrice === $this->price) ? null : $this->specialPrice;
        } elseif ($product->getTypeId() == 'grouped') {
            foreach ($product->getTypeInstance()->getAssociatedProducts($product) as $childProduct) {
                $childPrices[] = $childProduct->getPrice();
                if ($childProduct->getSpecialPrice() !== null) {
                    $childSpecialPrices[] = $childProduct->getSpecialPrice();
                }
            }
            $this->price = isset($childPrices) ? min($childPrices) : null;
            $this->specialPrice = isset($childSpecialPrices) ? min($childSpecialPrices) : null;
        } else {
            $this->price = $product->getPrice();
            $this->specialPrice = $product->getSpecialPrice();
        }
        $this->formatPriceValues();
    }

    /**
     * Formats the price values.
     *
     * @return null
     */

    private function formatPriceValues()
    {
        $this->price = (float)number_format(
            $this->price,
            2,
            '.',
            ''
        );

        $this->specialPrice = (float)number_format(
            $this->specialPrice,
            2,
            '.',
            ''
        );
    }
}
