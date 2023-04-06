<?php

namespace Dotdigitalgroup\Email\Model\Order;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Product\Attribute;
use Dotdigitalgroup\Email\Model\Product\AttributeFactory;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Item;

class OrderItemProcessor extends DataObject
{
    /**
     * @var Item
     */
    private $parentLineItem;

    /**
     * @var Item
     */
    private $productItem;

    /**
     * @var AttributeFactory
     */
    private $attributeHandler;

    /**
     * @var OrderItemOptionProcessor
     */
    private $orderItemOptionProcessor;

    /**
     * @var ?Product
     */
    private $productModel = null;

    /**
     * @var ?Product
     */
    private $childProductModel = null;

    /**
     * OrderItemProcessor constructor.
     *
     * @param AttributeFactory $attributeHandler
     * @param OrderItemOptionProcessor $orderItemOptionProcessor
     * @param array $data
     */
    public function __construct(
        AttributeFactory $attributeHandler,
        OrderItemOptionProcessor $orderItemOptionProcessor,
        array $data
    ) {
        $this->attributeHandler = $attributeHandler;
        $this->orderItemOptionProcessor = $orderItemOptionProcessor;

        parent::__construct($data);
    }

    /**
     * Process.
     *
     * @param Item $productItem
     * @return array|false
     * @throws LocalizedException
     */
    public function process(Item $productItem)
    {
        $this->setProductItemAndParentLineItem($productItem);
        $this->setProductModels();

        if ($this->productItem->getProductType() == 'configurable') {
            return false;
        }

        $productData = $this->getProductData();

        if ($this->isChildOfBundledProduct()) {
            $productData['isChildOfBundled'] = true;
        }

        return $productData;
    }

    /**
     * Set product models.
     *
     * @return void
     */
    private function setProductModels(): void
    {
        unset($this->productModel, $this->childProductModel);
        [$this->productModel, $this->childProductModel] =
            $this->isChildProduct()
                ? [$this->parentLineItem->getProduct() , $this->productItem->getProduct()]
                : [$this->productItem->getProduct() , null];
    }

    /**
     * Set product data.
     *
     * @param Item $productItem
     * @return void
     */
    private function setProductItemAndParentLineItem(Item $productItem): void
    {
        switch ($productItem->getProductType()) {
            case 'configurable':
            case 'bundle':
                $this->parentLineItem = $productItem;
                break;
        }
        $this->productItem = $productItem;
    }

    /**
     * Get product data payload.
     *
     * @return array
     * @throws LocalizedException
     */
    private function getProductData(): array
    {
        $price = $this->isChildOfConfigurableProduct()
            ? $this->parentLineItem->getPrice()
            : $this->productItem->getPrice();

        $priceInclTax = $this->isChildOfConfigurableProduct()
            ? $this->parentLineItem->getPriceInclTax()
            : $this->productItem->getPriceInclTax();

        /**
         * Product categories.
         */
        $productCat = $this->getCategoriesFromProductModel($this->productModel);
        $childProductCat = $this->getCategoriesFromProductModel($this->childProductModel);
        $this->mergeChildCategories($productCat, $childProductCat);

        /**
         * Product attributes
         */
        $configAttributes = $this->getProductAttributesToSync();
        $attributeSetName = $this->attributeHandler->create()
            ->getAttributeSetName($this->productModel);

        $attributes = $this->processProductAttributes($configAttributes, $this->productModel);
        $childAttributes = $this->processProductAttributes($configAttributes, $this->childProductModel);

        /**
         * Output
         */
        $productData = [
            'product_id' => $this->getProductId(),
            'parent_id' => $this->getParentId(),
            'name' => $this->productItem->getName(),
            'parent_name' => $this->getParentName(),
            'sku' => $this->getSku(),
            'qty' => (int) number_format(
                $this->productItem->getQtyOrdered(),
                2
            ),
            'price' => (float) number_format(
                (float) $price,
                2,
                '.',
                ''
            ),
            'price_inc_tax' => (float) number_format(
                (float) $priceInclTax,
                2,
                '.',
                ''
            ),
            'attribute-set' => $attributeSetName,
            'categories' => $productCat
        ];
        if ($attributes && $attributes->hasValues()) {
            $productData['product_attributes'] = $attributes->getProperties();
        }
        if ($childAttributes && $childAttributes->hasValues()) {
            $productData['child_product_attributes'] = $childAttributes->getProperties();
        }

        $customOptions = ($this->getIncludeCustomOptions())
            ? $this->orderItemOptionProcessor->process($this->productItem)
            : [];
        if ($customOptions) {
            $productData['custom-options'] = $customOptions;
        }

        return $productData;
    }

    /**
     * Is child product.
     *
     * @return bool
     */
    private function isChildProduct(): bool
    {
        if (isset($this->parentLineItem)
            && $this->parentLineItem->getId() === $this->productItem->getParentItemId()
        ) {
            return true;
        }

        return false;
    }

    /**
     * Is child of configurable product.
     *
     * @return bool
     */
    private function isChildOfConfigurableProduct(): bool
    {
        if ($this->isChildProduct() && $this->hasConfigurableParent()) {
            return true;
        }

        return false;
    }

    /**
     * Is child of bundled product.
     *
     * @return bool
     */
    private function isChildOfBundledProduct(): bool
    {
        if ($this->isChildProduct() && $this->hasBundleParent()) {
            return true;
        }

        return false;
    }

    /**
     * Load a product's categories.
     *
     * @param Product|null $product
     * @return array
     * @throws LocalizedException
     */
    private function getCategoriesFromProductModel(?Product $product): array
    {
        if (!$product) {
            return [];
        }

        $categoryCollection = $product->getCategoryCollection();
        /** @var \Magento\Eav\Model\Entity\Collection\AbstractCollection $categoryCollection */
        $categoryCollection->addAttributeToSelect('name');
        return $this->getCategoriesFromCollection($categoryCollection);
    }

    /**
     * Get categories from collection.
     *
     * @param AbstractCollection $categoryCollection
     * @return array
     */
    private function getCategoriesFromCollection(AbstractCollection $categoryCollection): array
    {
        $productCat = [];
        foreach ($categoryCollection as $cat) {
            $categories = [];
            $categories[] = $cat->getName();
            $productCat[]['Name'] = mb_substr(
                implode(', ', $categories),
                0,
                Data::DM_FIELD_LIMIT
            );
        }

        return $productCat;
    }

    /**
     * Merge child categories.
     *
     * @param array $productCat
     * @param array $childCategories
     */
    private function mergeChildCategories(array &$productCat, array $childCategories)
    {
        foreach ($childCategories as $childCategory) {
            if (!in_array($childCategory, $productCat)) {
                $productCat[] = $childCategory;
            }
        }
    }

    /**
     * Look up selected attributes in config.
     *
     * @return array
     */
    private function getProductAttributesToSync(): array
    {
        $configAttributes = $this->attributeHandler->create()
            ->getConfigAttributesForSync($this->getWebsiteId());

        if (!$configAttributes) {
            return [];
        }

        return explode(',', $configAttributes);
    }

    /**
     * Process product attributes.
     *
     * @param array $configAttributes
     * @param Product|null $product
     * @return Attribute|null
     */
    private function processProductAttributes(array $configAttributes, ?Product $product): ?Attribute
    {
        if (empty($configAttributes) || !$product) {
            return null;
        }
        $attributeModel = $this->attributeHandler->create();
        $productAttributesFromAttributeSet = $attributeModel->getAttributesArray(
            $product->getAttributeSetId()
        );

        return $attributeModel->processConfigAttributes(
            $configAttributes,
            $productAttributesFromAttributeSet,
            $product
        );
    }

    /**
     * Get product Id.
     *
     * @return string
     */
    private function getProductId(): string
    {
        return (string) $this->productItem->getProductId();
    }

    /**
     * Get parent id.
     *
     * @return string
     */
    private function getParentId(): string
    {
        if ($this->isChildProduct()) {
            return (string) $this->parentLineItem->getProductId();
        }

        return '';
    }

    /**
     * Is bundle product.
     *
     * @return bool
     */
    private function isBundle(): bool
    {
        return $this->productItem->getProductType() === 'bundle';
    }

    /**
     * Get product sku.
     *
     * Note the type cast here is NOT redundant - getSku can return null.
     *
     * @return string
     */
    private function getSku(): string
    {
        if ($this->productModel && $this->isBundle()) {
            return (string) $this->productModel->getSku();
        }
        return (string) $this->productItem->getSku();
    }

    /**
     * Get parent product name.
     *
     * @return string
     */
    private function getParentName(): string
    {
        if ($this->isChildProduct()) {
            return (string) $this->parentLineItem->getName();
        }
        return '';
    }

    /**
     * Get website id.
     *
     * @return string
     */
    private function getWebsiteId(): string
    {
        return $this->_getData('websiteId');
    }

    /**
     * Get include custom options.
     *
     * @return bool
     */
    private function getIncludeCustomOptions(): bool
    {
        return $this->_getData('includeCustomOptions');
    }

    /**
     * Is parent product configurable?.
     *
     * @return bool
     */
    private function hasConfigurableParent(): bool
    {
        return $this->parentLineItem->getProductType() === 'configurable';
    }

    /**
     * Is parent product bundle?.
     *
     * @return bool
     */
    private function hasBundleParent(): bool
    {
        return $this->parentLineItem->getProductType() === 'bundle';
    }
}
