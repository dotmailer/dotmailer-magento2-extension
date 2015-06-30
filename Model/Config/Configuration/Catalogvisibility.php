<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Catalogvisibility
{

    public function toOptionArray()
    {
	    return array();
        $options = Mage::getModel('catalog/product_visibility')->getAllOptions();
        array_shift($options);
        return $options;
    }
}