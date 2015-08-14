<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Rules_Type
{

    /**
     * get input type
     *
     * @param $attribute
     * @return string
     */
    public function getInputType($attribute)
    {
        switch ($attribute) {
            case 'subtotal': case 'grand_total': case 'items_qty':
                return 'numeric';

            case 'method': case 'shipping_method': case 'country_id': case 'region_id': case 'customer_group_id':
                return 'select';

            default:
                $attribute = Mage::getSingleton('eav/config')->getAttribute('catalog_product', $attribute);
                if($attribute->getFrontend()->getInputType() == 'price')
                    return 'numeric';
                if ($attribute->usesSource())
                    return 'select';
        }
        return 'string';
    }

    /**
     * default options
     *
     * @return array
     */
    public function defaultOptions()
    {
        return array(
            'method' => Mage::helper('adminhtml')->__('Payment Method'),
            'shipping_method' => Mage::helper('adminhtml')->__('Shipping Method'),
            'country_id' => Mage::helper('adminhtml')->__('Shipping Country'),
            'city' => Mage::helper('adminhtml')->__('Shipping Town'),
            'region_id' =>Mage::helper('adminhtml')->__( 'Shipping State/Province'),
            'customer_group_id' =>Mage::helper('adminhtml')->__( 'Customer Group'),
            'coupon_code' =>Mage::helper('adminhtml')->__( 'Coupon'),
            'subtotal' =>Mage::helper('adminhtml')->__( 'Subtotal'),
            'grand_total' =>Mage::helper('adminhtml')->__( 'Grand Total'),
            'items_qty' =>Mage::helper('adminhtml')->__( 'Total Qty'),
            'customer_email' => Mage::helper('adminhtml')->__('Email'),
        );
    }

    /**
     * attribute options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $defaultOptions = $this->defaultOptions();
        $productCondition = Mage::getModel('salesrule/rule_condition_product');
        $productAttributes = $productCondition->loadAttributeOptions()->getAttributeOption();
        $pAttributes = array();
        foreach ($productAttributes as $code=>$label) {
            if (strpos($code, 'quote_item_') === false) {
                $pAttributes[$code] = Mage::helper('adminhtml')->__($label);
            }
        }
        $options = array_merge($defaultOptions, $pAttributes);
        return $options;
    }
}