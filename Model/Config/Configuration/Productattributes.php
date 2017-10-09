<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Productattributes implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     */
    private $attributes;

    /**
     * Exclude incompatible product attributes from the mapping.
     * @var array
     */
    private $excluded = [
        'quantity_and_stock_status'
    ];

    /**
     * Productattributes constructor.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $collectionFactory
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $collectionFactory
    ) {
        $this->attributes = $collectionFactory;
    }

    /**
     * Get options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $attributes = $this->attributes
            ->create()
            ->addVisibleFilter();

        $attributeArray = [];
        $attributeArray[] = [
            'label' => __('---- Default Option ----'),
            'value' => '0',
        ];

        foreach ($attributes as $attribute) {
            $attributeCode = $attribute->getAttributeCode();

            if (!in_array($attributeCode, $this->excluded)) {
                $attributeArray[] = [
                    'label' => $attribute->getFrontendLabel(),
                    'value' => $attributeCode,
                ];
            }
        }
        return $attributeArray;
    }
}
