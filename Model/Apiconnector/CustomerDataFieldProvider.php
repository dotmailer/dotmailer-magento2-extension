<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

use Dotdigitalgroup\Email\Helper\Data;
use Magento\Framework\DataObject;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;

class CustomerDataFieldProvider extends DataObject
{
    /**
     * @var array
     */
    protected $additionalDataFields = [];

    /**
     * Customer data fields which aren't included
     *
     * @var array
     */
    private $ignoredFields = [
        'custom_attributes',
        'abandoned_prod_name',
    ];

    /**
     * @var Data
     */
    private $helper;

    /**
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($data);
    }

    /**
     * Get customer datafields mapped - exclude custom attributes.
     *
     * @return array
     */
    public function getCustomerDataFields()
    {
        //customer mapped data
        $store = $this->getWebsite()->getDefaultStore();
        $mappedData = $this->helper
            ->getScopeConfig()
            ->getValue('connector_data_mapping/customer_data', ScopeInterface::SCOPE_STORE, $store->getId())
            ?: [];

        return array_filter($mappedData += $this->getAdditionalDataFields(), function ($value, $key) {
            return $value && !in_array($key, $this->ignoredFields)
                ? $value
                : null;
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * @return array
     */
    public function getAdditionalDataFields()
    {
        return $this->additionalDataFields;
    }

    /**
     * @return WebsiteInterface
     */
    public function getWebsite()
    {
        return $this->_getData('website');
    }

    /**
     * Add field(s) to the ignore list
     *
     * @param string|array $fieldNames
     * @return $this
     */
    public function addIgnoredField($fieldNames)
    {
        if (is_array($fieldNames)) {
            $this->ignoredFields = array_merge($this->ignoredFields, $fieldNames);
        } else {
            $this->ignoredFields[] = $fieldNames;
        }
        return $this;
    }
}
