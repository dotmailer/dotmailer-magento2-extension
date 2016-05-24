<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Catalogtype
{

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * Catalogtype constructor.
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManagerInterface
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManagerInterface
    ) {
        $this->_objectManager = $objectManagerInterface;
    }

    /**
     * Return options.
     * 
     * @return mixed
     */
    public function toOptionArray()
    {
        $options
            = $this->_objectManager->create('Magento\Catalog\Model\Product\Type')
            ->getAllOptions();
        array_shift($options);

        return $options;
    }
}
