<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class CatalogType
{

    public function toOptionArray()
    {
	    return array();

        $options = Mage::getModel('catalog/product_type')->getAllOptions();
        array_shift($options);
        return $options;
    }
}