<?php

namespace Dotdigitalgroup\Email\Model\Product;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;

class Attribute
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var AttributeCollectionFactory
     */
    private $attributeCollection;

    /**
     * @var AttributeSetRepositoryInterface
     */
    private $attributeSet;

    /**
     * @var Product
     */
    private $productResource;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $productAttributeRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @var bool
     */
    private $hasValues;

    /**
     * @var array
     */
    private $properties = [];

    /**
     * Attribute constructor.
     *
     * @param Data $helper
     * @param AttributeCollectionFactory $attributeCollection
     * @param AttributeSetRepositoryInterface $attributeSet
     * @param Product $productResource
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param DateTimeFactory $dateTimeFactory
     */
    public function __construct(
        Data $helper,
        AttributeCollectionFactory $attributeCollection,
        AttributeSetRepositoryInterface $attributeSet,
        Product $productResource,
        ProductAttributeRepositoryInterface $productAttributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        DateTimeFactory $dateTimeFactory
    ) {
        $this->helper = $helper;
        $this->attributeCollection = $attributeCollection;
        $this->attributeSet = $attributeSet;
        $this->productResource = $productResource;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->dateTimeFactory = $dateTimeFactory;
    }

    /**
     * Get configuration attributes for sync.
     *
     * @param string|int $websiteId
     *
     * @return bool|string
     */
    public function getConfigAttributesForSync($websiteId)
    {
        return $this->helper->getWebsiteConfig(
            Config::XML_PATH_CONNECTOR_SYNC_PRODUCT_ATTRIBUTES,
            $websiteId
        );
    }

    /**
     * Get attributes from attribute set.
     *
     * @param int $attributeSetId
     *
     * @return array
     */
    public function getAttributesArray($attributeSetId)
    {
        $result = [];
        $attributes = $this->attributeCollection->create()
            ->setAttributeSetFilter($attributeSetId)
            ->getItems();

        foreach ($attributes as $attribute) {
            $result[] = $attribute->getAttributeCode();
        }

        return $result;
    }

    /**
     * Process configuration attributes.
     *
     * @param array $configAttributes
     * @param mixed $attributesFromAttributeSet
     * @param \Magento\Catalog\Model\Product $productModel
     *
     * @return $this
     */
    public function processConfigAttributes($configAttributes, $attributesFromAttributeSet, $productModel)
    {
        foreach ($configAttributes as $attributeCode) {
            //if config attribute is in attribute set
            if (in_array($attributeCode, $attributesFromAttributeSet)) {
                //attribute input type
                $inputType = $this->productResource
                    ->getAttribute($attributeCode)
                    ->getFrontend()
                    ->getInputType();

                //fetch attribute value from product depending on input type
                switch ($inputType) {
                    case 'multiselect':
                    case 'select':
                    case 'dropdown':
                        $value = $productModel->getAttributeText($attributeCode);
                        break;
                    case 'date':
                    case 'datetime':
                        $value = $this->dateTimeFactory->create()->date(
                            \DateTime::ATOM,
                            $productModel->getData($attributeCode)
                        );
                        break;
                    default:
                        $value = $productModel->getData($attributeCode);
                        break;
                }

                $this->processAttributeValue($value, $attributeCode);
            }
        }
        return $this;
    }

    /**
     * Process attribute value.
     *
     * @param string|array $value
     * @param string $attributeCode
     *
     * @return void
     */
    private function processAttributeValue($value, $attributeCode)
    {
        if (!$value) {
            return;
        }

        $this->hasValues = true;

        if (is_array($value)) {
            $values = $this->implodeRecursive(',', $value['values'] ?? $value);
            if ($values) {
                $this->properties[$attributeCode] = mb_substr($values, 0, Data::DM_FIELD_LIMIT);
            }
        } else {
            $this->properties[$attributeCode] = mb_substr($value, 0, Data::DM_FIELD_LIMIT);
        }
    }

    /**
     * Get attribute set name.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getAttributeSetName($product)
    {
        if (!$product) {
            return '';
        }
        try {
            $attributeSetRepository = $this->attributeSet->get($product->getAttributeSetId());
            return $attributeSetRepository->getAttributeSetName();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return __('Not available');
        }
    }

    /**
     * Has values.
     *
     * @return mixed
     */
    public function hasValues()
    {
        return $this->hasValues;
    }

    /**
     * Get media image attributes.
     *
     * @return \Magento\Catalog\Api\Data\ProductAttributeSearchResultsInterface
     */
    public function getMediaImageAttributes()
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('frontend_input', 'media_image')
            ->create();

        return $this->productAttributeRepository->getList($searchCriteria);
    }

    /**
     * Returns any dynamically set attribute properties as a regular stdClass.
     *
     * @return object
     */
    public function getProperties()
    {
        return (object) $this->properties;
    }

    /**
     * Implode recursive.
     *
     * @param string $separator
     * @param array $array
     *
     * @return string
     */
    private function implodeRecursive(string $separator, array $array)
    {
        $string = '';
        foreach ($array as $i => $a) {
            if (is_array($a)) {
                if ($string) {
                    $string .= $separator;
                }
                $string .= $this->implodeRecursive($separator, $a);
            } else {
                $string .= $a;
                if ($i < count($array) - 1) {
                    $string .= $separator;
                }
            }
        }

        return $string;
    }
}
