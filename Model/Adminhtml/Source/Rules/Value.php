<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules;

class Value
{

    protected $_configFactory;
    protected $_yesno;
    protected $_country;
    protected $_allregion;
    protected $_allShippingMethods;
    protected $_allPaymentMethods;
    protected $_group;

    public function __construct(
        \Magento\Eav\Model\ConfigFactory $configFactory,
        \Magento\Config\Model\Config\Source\Yesno $yesno,
        \Magento\Directory\Model\Config\Source\Country $country,
        \Magento\Directory\Model\Config\Source\Allregion $allregion,
        \Magento\Shipping\Model\Config\Source\Allmethods $allShippingMethods,
        \Magento\Payment\Model\Config\Source\Allmethods $allPaymentMethods,
        \Magento\Customer\Model\Config\Source\Group $group
    ) {
        $this->_configFactory = $configFactory->create();
        $this->_yesno = $yesno;
        $this->_country = $country;
        $this->_allregion = $allregion;
        $this->_allShippingMethods = $allShippingMethods;
        $this->_allPaymentMethods = $allPaymentMethods;
        $this->_group = $group;
    }

    /**
     * get element type
     *
     * @param $attribute
     *
     * @return string
     */
    public function getValueElementType($attribute)
    {
        switch ($attribute) {
            case 'method':
            case 'shipping_method':
            case 'country_id':
            case 'region_id':
            case 'customer_group_id':
                return 'select';
            default:
                $attribute
                    = $this->_configFactory->getAttribute('catalog_product',
                    $attribute);
                if ($attribute->usesSource()) {
                    return 'select';
                }
        }

        return 'text';
    }

    /**
     * get options array
     *
     * @param      $attribute
     * @param bool $is_empty
     *
     * @return array
     */
    public function getValueSelectOptions($attribute, $is_empty = false)
    {
        $options = array();
        if ($is_empty) {
            $options
                = $this->_yesno->toOptionArray();

            return $options;
        }

        switch ($attribute) {
            case 'country_id':
                $options = $this->_country->toOptionArray();
                break;

            case 'region_id':
                $options = $this->_allregion->toOptionArray();
                break;

            case 'shipping_method':
                $options = $this->_allShippingMethods->toOptionArray();
                break;

            case 'method':
                $options = $this->_allPaymentMethods->toOptionArray();
                break;

            case 'customer_group_id':
                $options = $this->_group->toOptionArray();
                break;

            default:
                $attribute
                    = $this->_configFactory->getAttribute('catalog_product',
                    $attribute);
                if ($attribute->usesSource()) {
                    $options = $attribute->getSource()->getAllOptions();
                }
        }

        return $options;
    }

    /**
     * options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->_allPaymentMethods->toOptionArray();
    }
}