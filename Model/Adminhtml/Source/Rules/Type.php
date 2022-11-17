<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules;

use Magento\Eav\Model\ConfigFactory;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\Exception\LocalizedException;
use Magento\SalesRule\Model\Rule\Condition\ProductFactory;

class Type
{
    /**
     * @var ConfigFactory
     */
    private $configFactory;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * Type constructor.
     *
     * @param ConfigFactory $configFactory
     * @param ProductFactory $productFactory
     */
    public function __construct(
        ConfigFactory $configFactory,
        ProductFactory $productFactory
    ) {
        $this->configFactory = $configFactory;
        $this->productFactory = $productFactory;
    }

    /**
     * Default options.
     *
     * @param string $attribute
     *
     * @return string
     * @throws LocalizedException
     */
    public function getInputType($attribute)
    {
        switch ($attribute) {
            case 'customer_email':
                return 'email';

            case 'subtotal':
            case 'grand_total':
            case 'items_qty':
                return 'numeric';

            case 'method':
            case 'shipping_method':
            case 'country_id':
            case 'region_id':
            case 'customer_group_id':
            case 'attribute_set_id':
                return 'select';

            default:
                $attribute = $this->configFactory
                    ->create()
                    ->getAttribute(
                        'catalog_product',
                        $attribute
                    );
                return $this->processAttribute($attribute);
        }
    }

    /**
     * Process attribute.
     *
     * @param AbstractAttribute $attribute
     *
     * @return string
     */
    private function processAttribute($attribute)
    {
        if ($attribute->getFrontend()->getInputType() == 'price') {
            return 'numeric';
        }
        if ($attribute->usesSource()) {
            return 'select';
        }

        return false;
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
        $productAttributes = $this->productFactory
            ->create()
            ->loadAttributeOptions()
            ->getAttributeOption();

        $pAttributes = [];
        foreach ($productAttributes as $code => $label) {
            if (strpos($code, 'quote_item_') === false) {
                $pAttributes[$code] = $label;
            }
        }
        return array_merge($defaultOptions, $pAttributes);
    }
}
