<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules;

class Type
{

	protected $_objectManager;
	protected $_configFactory;
	protected $_productFactory;

	public function __construct(
		\Magento\Eav\Model\ConfigFactory $configFactory,
		\Magento\SalesRule\Model\Rule\Condition\ProductFactory $productFactory,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface
	) {
		$this->_configFactory  = $configFactory->create();
		$this->_objectManager  = $objectManagerInterface;
		$this->_productFactory = $productFactory->create();
	}

	/**
	 * get input type
	 *
	 * @param $attribute
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
				$attribute = $this->_configFactory->getAttribute(
					'catalog_product', $attribute
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
	 * default options
	 *
	 * @return array
	 */
	public function defaultOptions()
	{
		return array(
			'method'            => 'Payment Method',
			'shipping_method'   => 'Shipping Method',
			'country_id'        => 'Shipping Country',
			'city'              => 'Shipping Town',
			'region_id'         => 'Shipping State/Province',
			'customer_group_id' => 'Customer Group',
			'coupon_code'       => 'Coupon',
			'subtotal'          => 'Subtotal',
			'grand_total'       => 'Grand Total',
			'items_qty'         => 'Total Qty',
			'customer_email'    => 'Email',
		);
	}

	/**
	 * attribute options array
	 *
	 * @return array
	 */
	public function toOptionArray()
	{
		$defaultOptions    = $this->defaultOptions();
		$productCondition  = $this->_productFactory;
		$productAttributes = $productCondition->loadAttributeOptions()
			->getAttributeOption();
		$pAttributes       = array();
		foreach ($productAttributes as $code => $label) {
			if (strpos($code, 'quote_item_') === false) {
				$pAttributes[$code] = __($label);
			}
		}
		$options = array_merge($defaultOptions, $pAttributes);

		return $options;
	}
}