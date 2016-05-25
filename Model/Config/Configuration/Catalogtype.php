<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Catalogtype
{

    protected $_productType;

    public function __construct(
        \Magento\Catalog\Model\Product\Type $productType
    ) {
        $this->_productType = $productType;
    }

    public function toOptionArray()
    {
        $options
            = $this->_productType->getAllOptions();
        array_shift($options);

        return $options;
    }
}