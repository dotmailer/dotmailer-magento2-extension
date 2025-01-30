<?php

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
use Dotdigitalgroup\Email\Model\Validator\Schema\Exception\SchemaValidationException;
use Dotdigitalgroup\Email\Model\Validator\Schema\SchemaValidator;
use Dotdigitalgroup\Email\Model\Validator\Schema\SchemaValidatorFactory;
use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\Catalog\Model\Product\Attribute\Source\StatusFactory;
use Magento\Catalog\Model\Product\VisibilityFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Api\TaxCalculationInterface;
use Magento\Tax\Helper\Data as TaxHelper;

/**
 * Transactional data for catalog products to sync.
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
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
     * @var UrlFinder
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
     * @var ImageFinder
     */
    private $imageFinder;

    /**
     * @var TierPriceFinderInterface
     */
    private $tierPriceFinder;

    /**
     * @var StockFinderInterface
     */
    private $stockFinderInterface;

    /**
     * @var CatalogSync
     */
    private $imageType;

    /**
     * @var TaxCalculationInterface
     */
    private $taxCalculation;

    /**
     * @var TaxHelper
     */
    protected TaxHelper $taxHelper;

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
     * @param UrlFinder $urlFinder
     * @param AttributeFactory $attributeHandler
     * @param ParentFinder $parentFinder
     * @param ImageFinder $imageFinder
     * @param TierPriceFinderInterface $tierPriceFinder
     * @param StockFinderInterface $stockFinderInterface
     * @param CatalogSync $imageType
     * @param TaxCalculationInterface $taxCalculation
     * @param TaxHelper $taxHelper
     * @param SchemaValidatorFactory $schemaValidatorFactory
     * @param DateTime $dateTime
     */
    public function __construct(
        StoreManagerInterface $storeManagerInterface,
        StatusFactory $statusFactory,
        VisibilityFactory $visibilityFactory,
        UrlFinder $urlFinder,
        AttributeFactory $attributeHandler,
        ParentFinder $parentFinder,
        ImageFinder $imageFinder,
        TierPriceFinderInterface $tierPriceFinder,
        StockFinderInterface $stockFinderInterface,
        CatalogSync $imageType,
        TaxCalculationInterface $taxCalculation,
        TaxHelper $taxHelper,
        SchemaValidatorFactory $schemaValidatorFactory,
        DateTime $dateTime
    ) {
        $this->visibilityFactory = $visibilityFactory;
        $this->statusFactory = $statusFactory;
        $this->storeManager = $storeManagerInterface;
        $this->urlFinder = $urlFinder;
        $this->attributeHandler = $attributeHandler;
        $this->parentFinder = $parentFinder;
        $this->imageFinder = $imageFinder;
        $this->tierPriceFinder = $tierPriceFinder;
        $this->stockFinderInterface = $stockFinderInterface;
        $this->imageType = $imageType;
        $this->taxCalculation = $taxCalculation;
        $this->taxHelper = $taxHelper;
        $this->schemaValidator = $schemaValidatorFactory->create(['pattern'=>static::SCHEMA_RULES]);
        $this->dateTime = $dateTime;
    }

    /**
     * Set the product data
     *
     * @param mixed $product
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

        $this->setPrices($product, $storeId);
        $this->setPricesIncTax($product, $storeId);

        $this->tierPrices = $this->tierPriceFinder->getTierPrices($product);

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
                unset($this->attributes);
                return;
            }

            $this->attributes = $attributeProperties;
        }
    }

    /**
     * Set prices for all product types.
     *
     * @param mixed $product
     * @param int|null $storeId
     *
     * @return void
     */
    private function setPrices($product, ?int $storeId)
    {
        if ($product->getTypeId() == 'configurable') {
            foreach ($product->getTypeInstance()->getUsedProducts($product) as $childProduct) {
                if ($storeId && !in_array($storeId, $childProduct->getStoreIds())) {
                    continue;
                }
                $childPrices[] = $childProduct->getPrice();
                if ($childProduct->getSpecialPrice() !== null) {
                    $childSpecialPrices[] = $childProduct->getSpecialPrice();
                }
            }
            $price = isset($childPrices) ? min($childPrices) : null;
            $specialPrice = isset($childSpecialPrices) ? min($childSpecialPrices) : null;
        } elseif ($product->getTypeId() == 'bundle') {
            $price = $product->getPriceInfo()->getPrice('regular_price')->getMinimalPrice()->getValue();
            $specialPrice = $product->getPriceInfo()->getPrice('final_price')->getMinimalPrice()->getValue();
            //if special price equals to price then its wrong.
            $specialPrice = ($specialPrice === $price) ? null : $specialPrice;
        } elseif ($product->getTypeId() == 'grouped') {
            foreach ($product->getTypeInstance()->getAssociatedProducts($product) as $childProduct) {
                $childPrices[] = $childProduct->getPrice();
                if ($childProduct->getSpecialPrice() !== null) {
                    $childSpecialPrices[] = $childProduct->getSpecialPrice();
                }
            }
            $price = isset($childPrices) ? min($childPrices) : null;
            $specialPrice = isset($childSpecialPrices) ? min($childSpecialPrices) : null;
        } else {
            $price = $product->getPrice();
            $specialPrice = $product->getSpecialPrice();
        }
        $this->price = $this->formatPriceValue($price);
        $this->specialPrice = $this->formatPriceValue($specialPrice);
    }

    /**
     * Set prices including tax.
     * In catalog sync, the rate is based on the (scoped) tax origin, as configured in
     * Default Tax Destination Calculation > Default Country.
     * Here we calculate the 'inc' figures with the rate and the prices we already obtained.
     *
     * @param MagentoProduct $product
     * @param string|int|null $storeId
     *
     * @return $this
     */
    private function setPricesIncTax($product, $storeId)
    {
        if (!$this->taxHelper->priceIncludesTax()) {
            $rate = $this->taxCalculation->getCalculatedRate(
                $product->getTaxClassId(),
                null,
                $storeId
            );
            $this->price_incl_tax = $this->adjustPricesWithTaxes($this->price, $rate);
            $this->specialPrice_incl_tax = $this->adjustPricesWithTaxes($this->specialPrice, $rate);
        } else {
            $this->price_incl_tax = $this->price;
            $this->specialPrice_incl_tax = $this->specialPrice;
        }

        return $this;
    }

    protected function adjustPricesWithTaxes($price, $taxRate)
    {
        return $this->formatPriceValue(
            $price + ($price * ($taxRate / 100))
        );
    }

    /**
     * Formats a price value.
     *
     * @param float|null $price
     *
     * @return float
     */
    private function formatPriceValue($price): float
    {
        return (float) number_format(
            (float) $price,
            2,
            '.',
            ''
        );
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
