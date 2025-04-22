<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Connector;

use Dotdigitalgroup\Email\Api\StockFinderInterface;
use Dotdigitalgroup\Email\Api\TierPriceFinderInterface;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Catalog\UrlFinder;
use Dotdigitalgroup\Email\Model\Product\Attribute as AttributeModel;
use Dotdigitalgroup\Email\Model\Product\AttributeFactory;
use Dotdigitalgroup\Email\Model\Product\ImageFinder;
use Dotdigitalgroup\Email\Model\Product\ImageType\Context\CatalogSync;
use Dotdigitalgroup\Email\Model\Product\ParentFinder;
use Dotdigitalgroup\Email\Model\Product\PriceFinderFactory;
use Dotdigitalgroup\Email\Model\Product\IndexPriceFinder;
use Dotdigitalgroup\Email\Model\Validator\Schema\Exception\SchemaValidationException;
use Dotdigitalgroup\Email\Model\Validator\Schema\SchemaValidator;
use Dotdigitalgroup\Email\Model\Validator\Schema\SchemaValidatorFactory;
use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\Catalog\Model\Product\Attribute\Source\StatusFactory;
use Magento\Catalog\Model\Product\VisibilityFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Transactional data for catalog products to sync.
 */
class Product extends AbstractConnectorModel
{
    public const TYPE_VARIANT = 'Variant';
    private const DEFAULT_PRODUCT_CREATED_DATE = '1970-01-01 01:00:00';

    /**
     * Dotdigital catalog required schema
     */
    public const SCHEMA_RULES = [
        'name' => ':isString',
        'price' => ':isFloat',
        'sku' => ':isString',
        'url' => ':url',
        'imagePath' => ':url',
        'created_date' => ':dateFormatAtom'
    ];

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
    public $price = 0.0;

    /**
     * @var float
     */
    public $price_incl_tax = 0.0;

    /**
     * @var float
     */
    public $specialPrice = 0.0;

    /**
     * @var float
     */
    public $specialPrice_incl_tax = 0.0;

    /**
     * @var array
     */
    public $tierPrices = [];

    /**
     * @var array
     */
    public $indexPrices = [];

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
    public $stock = 0.0;

    /**
     * @var array
     */
    public $websites = [];

    /**
     * @var string
     */
    public $type = '';

    /**
     * @var string
     */
    public $attribute_set = '';

    /**
     * @var string
     */
    public $created_date = '';

    /**
     * @var AttributeModel
     */
    public $attributes;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StatusFactory
     */
    private $statusFactory;

    /**
     * @var VisibilityFactory
     */
    private $visibilityFactory;

    /**
     * @var PriceFinderFactory
     */
    private $priceFinderFactory;

    /**
     * @var UrlFinder
     */
    private $urlFinder;

    /**
     * @var AttributeFactory
     */
    private $attributeHandler;

    /**
     * @var ParentFinder
     */
    private $parentFinder;

    /**
     * @var ImageFinder
     */
    private $imageFinder;

    /**
     * @var TierPriceFinderInterface
     */
    private $tierPriceFinder;

    /**
     * @var IndexPriceFinder
     */
    private $indexPriceFinder;

    /**
     * @var StockFinderInterface
     */
    private $stockFinderInterface;

    /**
     * @var CatalogSync
     */
    private $imageType;

    /**
     * @var SchemaValidator
     */
    private $schemaValidator;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * Product constructor.
     *
     * @param StoreManagerInterface $storeManagerInterface
     * @param StatusFactory $statusFactory
     * @param VisibilityFactory $visibilityFactory
     * @param PriceFinderFactory $priceFinderFactory
     * @param UrlFinder $urlFinder
     * @param AttributeFactory $attributeHandler
     * @param ParentFinder $parentFinder
     * @param ImageFinder $imageFinder
     * @param TierPriceFinderInterface $tierPriceFinder
     * @param IndexPriceFinder $indexPriceFinder
     * @param StockFinderInterface $stockFinderInterface
     * @param CatalogSync $imageType
     * @param SchemaValidatorFactory $schemaValidatorFactory
     * @param DateTime $dateTime
     */
    public function __construct(
        StoreManagerInterface $storeManagerInterface,
        StatusFactory $statusFactory,
        VisibilityFactory $visibilityFactory,
        PriceFinderFactory $priceFinderFactory,
        UrlFinder $urlFinder,
        AttributeFactory $attributeHandler,
        ParentFinder $parentFinder,
        ImageFinder $imageFinder,
        TierPriceFinderInterface $tierPriceFinder,
        IndexPriceFinder $indexPriceFinder,
        StockFinderInterface $stockFinderInterface,
        CatalogSync $imageType,
        SchemaValidatorFactory $schemaValidatorFactory,
        DateTime $dateTime
    ) {
        $this->visibilityFactory = $visibilityFactory;
        $this->statusFactory = $statusFactory;
        $this->storeManager = $storeManagerInterface;
        $this->priceFinderFactory = $priceFinderFactory;
        $this->urlFinder = $urlFinder;
        $this->attributeHandler = $attributeHandler;
        $this->parentFinder = $parentFinder;
        $this->imageFinder = $imageFinder;
        $this->tierPriceFinder = $tierPriceFinder;
        $this->indexPriceFinder = $indexPriceFinder;
        $this->stockFinderInterface = $stockFinderInterface;
        $this->imageType = $imageType;
        $this->schemaValidator = $schemaValidatorFactory->create(['pattern'=> static::SCHEMA_RULES]);
        $this->dateTime = $dateTime;
    }

    /**
     * Set the product data
     *
     * @param MagentoProduct $product
     * @param int|null $storeId
     * @return $this
     * @throws SchemaValidationException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function setProduct($product, ?int $storeId)
    {
        $this->id = $product->getId();
        $this->sku = $product->getSku();
        $this->name = $product->getName();
        $this->created_date = $this->dateTime->date(
            \DateTimeInterface::ATOM,
            $product->getCreatedAt() ?: self::DEFAULT_PRODUCT_CREATED_DATE
        );

        $this->status = $this->statusFactory->create()
            ->getOptionText($product->getStatus());

        $this->type = ucfirst((string) $product->getTypeId());

        $options = $this->visibilityFactory->create()
            ->getOptionArray();
        $this->visibility = (string)$options[$product->getVisibility()];

        $priceFinder = $this->priceFinderFactory->create();
        $this->price = $priceFinder->getPrice($product, $storeId);
        $this->specialPrice = $priceFinder->getSpecialPrice($product, $storeId);
        $this->price_incl_tax = $priceFinder->getPriceInclTax($product, $storeId);
        $this->specialPrice_incl_tax = $priceFinder->getSpecialPriceInclTax($product, $storeId);

        $this->tierPrices = $this->tierPriceFinder->getTierPrices($product);
        $this->indexPrices = $this->indexPriceFinder->getIndexPrices($product, $storeId);

        $this->url = $this->urlFinder->fetchFor($product);

        $this->imagePath = $this->imageFinder->getImageUrl(
            $product,
            $this->imageType->getImageType(
                $this->storeManager->getStore($storeId)->getWebsiteId()
            )
        );

        $this->stock = (float) number_format(
            (float) $this->stockFinderInterface->getStockQty(
                $product,
                (int) $this->storeManager->getStore($storeId)->getWebsiteId()
            ),
            2,
            '.',
            ''
        );

        //limit short description
        $this->shortDescription = mb_substr(
            (string) $product->getShortDescription(),
            0,
            Data::DM_FIELD_LIMIT
        );

        //category data
        $count = 0;
        $categoryCollection = $product->getCategoryCollection();
        /** @var CategoryCollection $categoryCollection */
        $categoryCollection->addNameToResult();
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

        $this->processProductAttributes($product, $storeId);
        $this->processParentProducts($product);

        if (!$this->schemaValidator->isValid($this->toArray())) {
            throw new SchemaValidationException(
                $this->schemaValidator,
                __("Validation error")
            );
        };

        return $this;
    }

    /**
     * Retrieve product attributes for catalog sync.
     *
     * @param MagentoProduct $product
     * @param string|int|null $storeId
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function processProductAttributes(MagentoProduct $product, $storeId): void
    {
        $attributeModel = $this->attributeHandler->create();
        $this->attribute_set = $attributeModel->getAttributeSetName($product);
        $this->attributes = $attributeModel;

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
            $attributeProperties = $attributeModel->processConfigAttributes(
                $configAttributes,
                $attributesFromAttributeSet,
                $product
            )->getProperties();

            if (empty($attributeProperties)) {
                $this->attributes = null;
                return;
            }

            $this->attributes = $attributeProperties;
        }
    }

    /**
     * Process parent products
     *
     * @param MagentoProduct $product
     */
    private function processParentProducts(MagentoProduct $product)
    {
        $parentId = $this->parentFinder->getProductParentIdToCatalogSync($product);
        if ($parentId) {
            $this->parent_id = $parentId;
            $this->type = self::TYPE_VARIANT;
        }
    }
}
