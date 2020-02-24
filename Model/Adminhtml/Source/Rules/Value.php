<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules;

use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class Value
{
    /**
     * @var \Magento\Eav\Model\ConfigFactory
     */
    private $configFactory;

    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    private $yesno;

    /**
     * @var \Magento\Directory\Model\Config\Source\Country
     */
    private $country;

    /**
     * @var \Magento\Directory\Model\Config\Source\Allregion
     */
    private $allregion;

    /**
     * @var \Magento\Shipping\Model\Config\Source\Allmethods
     */
    private $allShippingMethods;

    /**
     * @var \Magento\Payment\Model\Config\Source\Allmethods
     */
    private $allPaymentMethods;

    /**
     * @var \Magento\Customer\Model\Config\Source\Group
     */
    private $sourceGroup;

    /**
     * @var AttributeSetRepositoryInterface
     */
    private $attributeSetRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

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
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        \Magento\Eav\Model\ConfigFactory $configFactory,
        \Magento\Config\Model\Config\Source\Yesno $yesno,
        \Magento\Directory\Model\Config\Source\Country $country,
        \Magento\Directory\Model\Config\Source\Allregion $allregion,
        \Magento\Shipping\Model\Config\Source\Allmethods $allShippingMethods,
        \Magento\Payment\Model\Config\Source\Allmethods $allPaymentMethods,
        \Magento\Customer\Model\Config\Source\Group $group,
        AttributeSetRepositoryInterface $attributeSetRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->configFactory      = $configFactory->create();
        $this->yesno              = $yesno;
        $this->country            = $country;
        $this->allregion          = $allregion;
        $this->allShippingMethods = $allShippingMethods;
        $this->allPaymentMethods  = $allPaymentMethods;
        $this->sourceGroup              = $group;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Get element type.
     *
     * @param string $attribute
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
            case 'attribute_set_id':
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
     * @param string $attribute
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

            case 'attribute_set_id':
                $attributeSetList = $this->attributeSetRepository->getList(
                    $this->searchCriteriaBuilder->addFilter('attribute_set_id', null, 'neq')->create()
                );
                foreach ($attributeSetList->getItems() as $attributeSet) {
                    $options[] = [
                        "value" => $attributeSet->getAttributeSetId(),
                        "label" => $attributeSet->getAttributeSetName()
                    ];
                }
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
