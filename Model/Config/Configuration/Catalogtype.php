<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Catalogtype implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Magento\Catalog\Model\Product\Type
     */
    private $productType;

    /**
     * Catalogtype constructor.
     *
     * @param \Magento\Catalog\Model\Product\Type $productType
     */
    public function __construct(
        \Magento\Catalog\Model\Product\Type $productType
    ) {
        $this->productType = $productType;
    }

    /**
     * Return options.
     *
     * @return mixed
     */
    public function toOptionArray()
    {
        $options = $this->productType->getAllOptions();
        //Add default option to first key of array. First key has empty value and empty label.
        $options[0]['value'] = '0';
        $options[0]['label'] = '---- Default Option ----';

        return $options;
    }
}
