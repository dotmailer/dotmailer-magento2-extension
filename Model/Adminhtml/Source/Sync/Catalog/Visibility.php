<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Sync_Catalog_Visibility
{
    /**
     * Options getter. Styling options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = Mage::getModel('catalog/product_visibility')->getAllOptions();
        array_shift($options);
        return $options;
    }
}