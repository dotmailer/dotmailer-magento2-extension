<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules;

class Value
{
    /**
     * @var
     */
    public $configFactory;
    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    public $yesno;
    /**
     * @var \Magento\Directory\Model\Config\Source\Country
     */
    public $country;
    /**
     * @var \Magento\Directory\Model\Config\Source\Allregion
     */
    public $allregion;
    /**
     * @var \Magento\Shipping\Model\Config\Source\Allmethods
     */
    public $allShippingMethods;
    /**
     * @var \Magento\Payment\Model\Config\Source\Allmethods
     */
    public $allPaymentMethods;
    /**
     * @var \Magento\Customer\Model\Config\Source\Group
     */
    public $sourceGroup;

    /**
     * Value constructor.
     *
     * @param \Magento\Eav\Model\ConfigFactory $configFactory
     * @param \Magento\Config\Model\Config\Source\Yesno $yesno
     * @param \Magento\Directory\Model\Config\Source\Country $country
     * @param \Magento\Directory\Model\Config\Source\Allregion $allregion
     * @param \Magento\Shipping\Model\Config\Source\Allmethods $allShippingMethods
     * @param \Magento\Payment\Model\Config\Source\Allmethods $allPaymentMethods
     * @param \Magento\Customer\Model\Config\Source\Group $group
     */
    public function __construct(
        \Magento\Eav\Model\ConfigFactory $configFactory,
        \Magento\Config\Model\Config\Source\Yesno $yesno,
        \Magento\Directory\Model\Config\Source\Country $country,
        \Magento\Directory\Model\Config\Source\Allregion $allregion,
        \Magento\Shipping\Model\Config\Source\Allmethods $allShippingMethods,
        \Magento\Payment\Model\Config\Source\Allmethods $allPaymentMethods,
        \Magento\Customer\Model\Config\Source\Group $group
    ) {
        $this->configFactory      = $configFactory->create();
        $this->yesno              = $yesno;
        $this->country            = $country;
        $this->allregion          = $allregion;
        $this->allShippingMethods = $allShippingMethods;
        $this->allPaymentMethods  = $allPaymentMethods;
        $this->sourceGroup              = $group;
    }

    /**
     * Get element type.
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
                    = $this->configFactory->getAttribute(
                        'catalog_product',
                        $attribute
                    );
                if ($attribute->usesSource()) {
                    return 'select';
                }
        }

        return 'text';
    }

    /**
     * Get options array.
     *
     * @param      $attribute
     * @param bool $isEmpty
     *
     * @return array
     */
    public function getValueSelectOptions($attribute, $isEmpty = false)
    {
        $options = [];
        if ($isEmpty) {
            $options
                = $this->yesno->toOptionArray();

            return $options;
        }

        switch ($attribute) {
            case 'country_id':
                $options = $this->country->toOptionArray();
                break;

            case 'region_id':
                $options = $this->allregion->toOptionArray();
                break;

            case 'shipping_method':
                $options = $this->allShippingMethods->toOptionArray();
                break;

            case 'method':
                $options = $this->allPaymentMethods->toOptionArray();
                break;

            case 'customer_group_id':
                $options = $this->sourceGroup->toOptionArray();
                break;

            default:
                $attribute
                    = $this->configFactory->getAttribute(
                        'catalog_product',
                        $attribute
                    );
                if ($attribute->usesSource()) {
                    $options = $attribute->getSource()->getAllOptions();
                }
        }

        return $options;
    }

    /**
     * Options array.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->allPaymentMethods->toOptionArray();
    }
}
