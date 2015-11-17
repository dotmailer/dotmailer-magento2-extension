<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules;

class Value
{
	protected $_objectManager;
	protected $_configFactory;

	public function __construct(
		\Magento\Eav\Model\ConfigFactory $configFactory,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface
	)
	{
		$this->_configFactory = $configFactory;
		$this->_objectManager = $objectManagerInterface;
	}
    /**
     * get element type
     *
     * @param $attribute
     * @return string
     */
    public function getValueElementType($attribute)
    {
        switch ($attribute) {
            case 'method': case 'shipping_method': case 'country_id': case 'region_id': case 'customer_group_id':
                return 'select';
            default:
                $attribute = $this->_configFactory->getAttribute('catalog_product', $attribute);
                if ($attribute->usesSource()) {
                    return 'select';
                }
        }
        return 'text';
    }

    /**
     * get options array
     *
     * @param $attribute
     * @param bool $is_empty
     * @return array
     */
    public function getValueSelectOptions($attribute, $is_empty = false)
    {
        $options = array();
        if($is_empty){
            $options = $this->_objectManager->create('Magento\Config\Model\Config\Source\Yesno')
                ->toOptionArray();
            return $options;
        }

        switch ($attribute) {
            case 'country_id':
                $options = $this->_objectManager->create('Magento\Config\Model\Config\Source\Country')
                    ->toOptionArray();
                break;

            case 'region_id':
                $options = $this->_objectManager->create('Magento\Directory\Model\Config\Source\Allregion')
                    ->toOptionArray();
                break;

            case 'shipping_method':
                $options = $this->_objectManager->create('Magento\Shipping\Model\Config\Source\Allmethods')
                    ->toOptionArray();
                break;

            case 'method':
                $options = $this->_objectManager->create('Magento\Shipping\Model\Config\Source\Allmethods')
                    ->toOptionArray();
                break;

            case 'customer_group_id':
                $options = $this->_objectManager->create('Magento\Customer\Model\Config\Source\Group')
                    ->toOptionArray();
                break;

            default:
                $attribute = $this->_config->getAttribute('catalog_product', $attribute);
                if ($attribute->usesSource()) {
                    $options = $attribute->getSource()->getAllOptions(false);
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
        $options = $this->_objectManager->create('Magento\Shipping\Model\Config\Source\Allmethods')
            ->toOptionArray();

        return $options;
    }
}