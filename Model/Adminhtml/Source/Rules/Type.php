<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules;

/**
 * Class Type
 * @package Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules
 */
class Type
{
    /**
     * @var
     */
    public $configFactory;
    /**
     * @var
     */
    public $productFactory;

    /**
     * Type constructor.
     *
     * @param \Magento\Eav\Model\ConfigFactory                       $configFactory
     * @param \Magento\SalesRule\Model\Rule\Condition\ProductFactory $productFactory
     */
    public function __construct(
        \Magento\Eav\Model\ConfigFactory $configFactory,
        \Magento\SalesRule\Model\Rule\Condition\ProductFactory $productFactory
    ) {
        $this->configFactory  = $configFactory->create();
        $this->productFactory = $productFactory->create();
    }

    /**
     * Get input type.
     *
     * @param string $attribute
     *
     * @return string
     */
    public function getInputType($attribute)
    {
        switch ($attribute) {
            case 'subtotal':
            case 'grand_total':
            case 'items_qty':
                return 'numeric';

            case 'method':
            case 'shipping_method':
            case 'country_id':
            case 'region_id':
            case 'customer_group_id':
                return 'select';

            default:
                $attribute = $this->configFactory->getAttribute(
                    'catalog_product',
                    $attribute
                );
                if ($attribute->getFrontend()->getInputType() == 'price') {
                    return 'numeric';
                }
                if ($attribute->usesSource()) {
                    return 'select';
                }
        }

        return 'string';
    }

    /**
     * Default options.
     *
     * @return array
     */
    public function defaultOptions()
    {
        return [
            'method' => 'Payment Method',
            'shipping_method' => 'Shipping Method',
            'country_id' => 'Shipping Country',
            'city' => 'Shipping Town',
            'region_id' => 'Shipping State/Province',
            'customer_group_id' => 'Customer Group',
            'coupon_code' => 'Coupon',
            'subtotal' => 'Subtotal',
            'grand_total' => 'Grand Total',
            'items_qty' => 'Total Qty',
            'customer_email' => 'Email',
        ];
    }

    /**
     * Attribute options array.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $defaultOptions = $this->defaultOptions();
        $productCondition = $this->productFactory;
        $productAttributes = $productCondition->loadAttributeOptions()
            ->getAttributeOption();
        $pAttributes = [];
        foreach ($productAttributes as $code => $label) {
            if (strpos($code, 'quote_item_') === false) {
                $pAttributes[$code] = __($label);
            }
        }
        $options = array_merge($defaultOptions, $pAttributes);

        return $options;
    }
}
