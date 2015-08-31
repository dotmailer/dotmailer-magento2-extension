<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Catalogvisibility
{
	protected $_objectManager;

	public function __construct(
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface
	)
	{
		$this->_objectManager = $objectManagerInterface;
	}

    public function toOptionArray()
    {
        $options = $this->_objectManager->create('Magento\Catalog\Model\Product\Visisbility')->getAllOptions();
        array_shift($options);
        return $options;
    }
}