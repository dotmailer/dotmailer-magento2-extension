<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Productattributes
{
    protected $_attributes;

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $collection
    )
    {
        $this->_attributes = $collection;
    }

    public function toOptionArray()
    {
        $attributes = $this->_attributes
            ->addVisibleFilter();

        $attributeArray = array(
            array(
                'label' => 'Select Attribute....',
                'value' => ''
            )
        );

        foreach($attributes as $attribute){
            $attributeArray[] = array(
                'label' => $attribute->getFrontendLabel(),
                'value' => $attribute->getAttributeCode()
            );
        }
        return $attributeArray;
    }
}