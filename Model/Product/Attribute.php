<?php

namespace Dotdigitalgroup\Email\Model\Product;

use Dotdigitalgroup\Email\Helper;

class Attribute
{
    /**
     * @var Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     */
    private $attributeCollection;

    /**
     * @var \Magento\Eav\Api\AttributeSetRepositoryInterface
     */
    private $attributeSet;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    private $productResource;

    /**
     * @var
     */
    private $hasValues;

    /**
     * Attribute constructor.
     *
     * @param Helper\Data $helper
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeCollection
     * @param \Magento\Eav\Api\AttributeSetRepositoryInterface $attributeSet
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResource
     */
    public function __construct(
        Helper\Data $helper,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeCollection,
        \Magento\Eav\Api\AttributeSetRepositoryInterface $attributeSet,
        \Magento\Catalog\Model\ResourceModel\Product $productResource
    ) {
        $this->helper = $helper;
        $this->attributeCollection = $attributeCollection;
        $this->attributeSet = $attributeSet;
        $this->productResource = $productResource;
    }

    /**
     * @param $websiteId
     *
     * @return bool|string
     */
    public function getConfigAttributesForSync($websiteId)
    {
        return $this->helper->getWebsiteConfig(
            Helper\Config::XML_PATH_CONNECTOR_SYNC_PRODUCT_ATTRIBUTES,
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
     * @param mixed $configAttributes
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
                        $value = $productModel->getData($attributeCode);
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

        if (!is_array($value)) {
            // check limit on text and assign value to array
            $this->$attributeCode = mb_substr($value, 0, Helper\Data::DM_FIELD_LIMIT);
        } elseif (is_array($value)) {
            $values = (isset($value['values'])) ? implode(',', $value['values']) : implode(',', $value);

            if ($values) {
                $this->$attributeCode = mb_substr($values, 0, Helper\Data::DM_FIELD_LIMIT);
            }
        }
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getAttributeSetName($product)
    {
        $attributeSetRepository = $this->attributeSet->get($product->getAttributeSetId());
        return $attributeSetRepository->getAttributeSetName();
    }

    /**
     * @return mixed
     */
    public function hasValues()
    {
        return $this->hasValues;
    }
}
