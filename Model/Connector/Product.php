<?php

namespace Dotdigitalgroup\Email\Model\Connector;

use Dotdigitalgroup\Email\Model\Product\AttributeFactory;
use Dotdigitalgroup\Email\Model\Product\ParentFinder;
use Dotdigitalgroup\Email\Api\TierPriceFinderInterface;
use Dotdigitalgroup\Email\Api\StockFinderInterface;

/**
 * Transactional data for catalog products to sync.
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Product
{
    const TYPE_VARIANT = 'Variant';

    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $parent_id = '';

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
    public $tierPrices = [];

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
     * @var \Dotdigitalgroup\Email\Model\Catalog\UrlFinder
     */
    private $urlFinder;

    /**
     * @var AttributeFactory $attributeHandler
     */
    private $attributeHandler;

    /**
     * @var ParentFinder
     */
    private $parentFinder;

    /**
     * @var TierPriceFinderInterface
     */
    private $tierPriceFinder;

    /**
     * @var StockFinderInterface
     */
    private $stockFinderInterface;

    /**
     * Product constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Catalog\Model\Product\Attribute\Source\StatusFactory $statusFactory
     * @param \Magento\Catalog\Model\Product\VisibilityFactory $visibilityFactory
     * @param \Dotdigitalgroup\Email\Model\Catalog\UrlFinder $urlFinder
     * @param AttributeFactory $attributeHandler
     * @param ParentFinder $parentFinder
     * @param TierPriceFinderInterface $tierPriceFinder
     * @param StockFinderInterface $stockFinderInterface
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Catalog\Model\Product\Attribute\Source\StatusFactory $statusFactory,
        \Magento\Catalog\Model\Product\VisibilityFactory $visibilityFactory,
        \Dotdigitalgroup\Email\Model\Catalog\UrlFinder $urlFinder,
        AttributeFactory $attributeHandler,
        ParentFinder $parentFinder,
        TierPriceFinderInterface $tierPriceFinder,
        StockFinderInterface $stockFinderInterface
    ) {
        $this->visibilityFactory = $visibilityFactory;
        $this->statusFactory = $statusFactory;
        $this->helper = $helper;
        $this->storeManager = $storeManagerInterface;
        $this->urlFinder = $urlFinder;
        $this->attributeHandler = $attributeHandler;
        $this->parentFinder = $parentFinder;
        $this->tierPriceFinder = $tierPriceFinder;
        $this->stockFinderInterface = $stockFinderInterface;
    }

    /**
     * Set the product data
     * @param $product
     * @param $storeId
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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

        $this->stock = (float)number_format($this->stockFinderInterface->getStockQty($product), 2, '.', '');

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
            $this->websites[$count]['Name'] = $website->getName() ?: '';
            ++$count;
        }

        $this->processProductOptions($product, $storeId);
        $this->processParentProducts($product);

        unset(
            $this->visibilityFactory,
            $this->statusFactory,
            $this->helper,
            $this->storeManager,
            $this->attributeHandler,
            $this->parentFinder,
            $this->tierPriceFinder,
            $this->stockFinderInterface
        );

        return $this;
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
                'visibilityFactory',
                'statusFactory',
                'storeManager',
                'urlFinder',
                'stockFinderInterface',
                'attributeHandler',
                'parentFinder',
                'tierPriceFinder'
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
        $this->tierPrices = $this->tierPriceFinder->getTierPrices($product);
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

    /**
     * @param $product
     */
    private function processParentProducts($product)
    {
        $parentId = $this->parentFinder->getProductParentIdToCatalogSync($product);

        if ($parentId) {
            $this->parent_id = $parentId;
            $this->type = self::TYPE_VARIANT;
        }
    }
}
