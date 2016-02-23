<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class CatalogType
{

    protected $_objectManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManagerInterface
    ) {
        $this->_objectManager = $objectManagerInterface;
    }

    public function toOptionArray()
    {
        $options
            = $this->_objectManager->create('Magento\Catalog\Model\Product\Type')
            ->getAllOptions();
        array_shift($options);

        return $options;
    }
}