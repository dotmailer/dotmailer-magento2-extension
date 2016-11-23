<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Productattributes implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    public $attributes;

    /**
     * Productattributes constructor.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $collection
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $collection
    ) {
        $this->attributes = $collection;
    }

    /**
     * Get options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $attributes = $this->attributes
            ->addVisibleFilter();

        $attributeArray = [];
        $attributeArray[] = [
            'label' => __('---- Default Option ----'),
            'value' => '0',
        ];

        foreach ($attributes as $attribute) {
            $attributeArray[] = [
                'label' => $attribute->getFrontendLabel(),
                'value' => $attribute->getAttributeCode(),
            ];
        }

        return $attributeArray;
    }
}
