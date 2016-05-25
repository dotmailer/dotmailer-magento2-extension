<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules;

class Value
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;
    /**
     * @var
     */
    protected $_configFactory;

    /**
     * Value constructor.
     *
     * @param \Magento\Eav\Model\ConfigFactory          $configFactory
     * @param \Magento\Framework\ObjectManagerInterface $objectManagerInterface
     */
    public function __construct(
        \Magento\Eav\Model\ConfigFactory $configFactory,
        \Magento\Framework\ObjectManagerInterface $objectManagerInterface
    ) {
        $this->_configFactory = $configFactory->create();
        $this->_objectManager = $objectManagerInterface;
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
                    = $this->_configFactory->getAttribute('catalog_product',
                    $attribute);
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
                = $this->_objectManager->create('Magento\Config\Model\Config\Source\Yesno')
                ->toOptionArray();

            return $options;
        }

        switch ($attribute) {
            case 'country_id':
                $options
                    = $this->_objectManager->create('Magento\Directory\Model\Config\Source\Country')
                    ->toOptionArray();
                break;

            case 'region_id':
                $options
                    = $this->_objectManager->create('Magento\Directory\Model\Config\Source\Allregion')
                    ->toOptionArray();
                break;

            case 'shipping_method':
                $options
                    = $this->_objectManager->create('Magento\Shipping\Model\Config\Source\Allmethods')
                    ->toOptionArray();
                break;

            case 'method':
                $options
                    = $this->_objectManager->create('Magento\Payment\Model\Config\Source\Allmethods')
                    ->toOptionArray();
                break;

            case 'customer_group_id':
                $options
                    = $this->_objectManager->create('Magento\Customer\Model\Config\Source\Group')
                    ->toOptionArray();
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
     * Options array.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options
            = $this->_objectManager->create('Magento\Payment\Model\Config\Source\Allmethods')
            ->toOptionArray();

        return $options;
    }
}
