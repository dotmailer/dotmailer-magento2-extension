<?php

namespace Dotdigitalgroup\Email\Model;

use Dotdigitalgroup\Email\Model\Config\Json;

/**
 * Class Rules
 * @package Dotdigitalgroup\Email\Model
 */
class Rules extends \Magento\Framework\Model\AbstractModel
{
    const ABANDONED = 1;
    const REVIEW = 2;

    /**
     * @var
     */
    public $conditionMap;
    /**
     * @var
     */
    public $defaultOptions;
    /**
     * @var
     */
    public $attributeMapForQuote;
    /**
     * @var
     */
    public $attributeMapForOrder;
    /**
     * @var
     */
    public $productAttribute;
    /**
     * @var array
     */
    public $used = [];

    /**
     * @var Adminhtml\Source\Rules\Type
     */
    public $rulesType;

    /**
     * @var \Magento\Eav\Model\Config
     */
    public $config;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    public $coreResource;

    /**
     * @var Json
     */
    public $serializer;

    /**
     * Rules constructor.
     * @param Adminhtml\Source\Rules\Type $rulesType
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Eav\Model\Config $config
     * @param Json $serializer
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Type $rulesType,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Eav\Model\Config $config,
        \Dotdigitalgroup\Email\Model\Config\Json $serializer,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->serializer = $serializer;
        $this->coreResource = $resourceConnection;
        $this->config       = $config;
        $this->rulesType    = $rulesType;
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Construct.
     */
    public function _construct()
    {
        $this->defaultOptions = $this->rulesType->defaultOptions();

        $this->conditionMap         = [
            'eq' => 'neq',
            'neq' => 'eq',
            'gteq' => 'lteq',
            'lteq' => 'gteq',
            'gt' => 'lt',
            'lt' => 'gt',
            'like' => 'nlike',
            'nlike' => 'like',
        ];
        $this->attributeMapForQuote = [
            'method' => 'method',
            'shipping_method' => 'shipping_method',
            'country_id' => 'country_id',
            'city' => 'city',
            'region_id' => 'region_id',
            'customer_group_id' => 'main_table.customer_group_id',
            'coupon_code' => 'main_table.coupon_code',
            'subtotal' => 'main_table.subtotal',
            'grand_total' => 'main_table.grand_total',
            'items_qty' => 'main_table.items_qty',
            'customer_email' => 'main_table.customer_email',
        ];
        $this->attributeMapForOrder = [
            'method' => 'method',
            'shipping_method' => 'main_table.shipping_method',
            'country_id' => 'country_id',
            'city' => 'city',
            'region_id' => 'region_id',
            'customer_group_id' => 'main_table.customer_group_id',
            'coupon_code' => 'main_table.coupon_code',
            'subtotal' => 'main_table.subtotal',
            'grand_total' => 'main_table.grand_total',
            'items_qty' => 'items_qty',
            'customer_email' => 'main_table.customer_email',
        ];
        parent::_construct();
        $this->_init('Dotdigitalgroup\Email\Model\ResourceModel\Rules');
    }

    /**
     * @return $this
     * @codingStandardsIgnoreStart
     */
    public function beforeSave()
    {

        parent::beforeSave();
        //@codingStandardsIgnoreEnd
        if ($this->isObjectNew()) {
            $this->setCreatedAt(time());
        } else {
            $this->setUpdatedAt(time());
        }
        $this->setCondition($this->serializer->serialize($this->getCondition()));
        $this->setWebsiteIds(implode(',', $this->getWebsiteIds()));

        return $this;
    }

    /**
     * After load.
     *
     * @return $this
     */
    public function _afterLoad()
    {
        parent::_afterLoad();

        $this->setCondition($this->serializer->unserialize($this->getCondition()));

        return $this;
    }

    /**
     * Check if rule already exist for website.
     *
     * @param      $websiteId
     * @param      $type
     * @param bool $ruleId
     *
     * @return bool
     */
    public function checkWebsiteBeforeSave($websiteId, $type, $ruleId = false)
    {
        $collection = $this->getCollection();
        $collection
            ->addFieldToFilter('type', ['eq' => $type])
            ->addFieldToFilter('website_ids', ['finset' => $websiteId]);
        if ($ruleId) {
            $collection->addFieldToFilter('id', ['neq' => $ruleId]);
        }
        $collection->setPageSize(1);

        if ($collection->getSize()) {
            return false;
        }

        return true;
    }

    /**
     * Get rule for website.
     *
     * @param $type
     * @param $websiteId
     *
     * @return array|\Magento\Framework\DataObject
     */
    public function getActiveRuleForWebsite($type, $websiteId)
    {
        $collection = $this->getCollection();
        $collection
            ->addFieldToFilter('type', ['eq' => $type])
            ->addFieldToFilter('status', ['eq' => 1])
            ->addFieldToFilter('website_ids', ['finset' => $websiteId])
            ->setPageSize(1);
        if ($collection->getSize()) {
            //@codingStandardsIgnoreStart
            return $collection->getFirstItem();
            //@codingStandardsIgnoreEnd
        }

        return [];
    }

    /**
     * Process rule on collection.
     *
     * @param $collection
     * @param $type
     * @param $websiteId
     *
     * @return mixed
     */
    public function process($collection, $type, $websiteId)
    {
        $rule = $this->getActiveRuleForWebsite($type, $websiteId);
        //if no rule then return the collection untouched
        if (empty($rule)) {
            return $collection;
        }

        //if rule has no conditions then return the collection untouched
        $condition = $this->serializer->unserialize($rule->getCondition());

        if (empty($condition)) {
            return $collection;
        }

        //join tables to collection according to type
        if ($type == self::ABANDONED) {
            $collection->getSelect()
                ->joinLeft(
                    ['quote_address' => $this->coreResource->getTableName('quote_address')],
                    'main_table.entity_id = quote_address.quote_id',
                    ['shipping_method', 'country_id', 'city', 'region_id']
                )->joinLeft(
                    ['quote_payment' => $this->coreResource->getTableName('quote_payment')],
                    'main_table.entity_id = quote_payment.quote_id',
                    ['method']
                )->where('address_type = ?', 'shipping');
        } elseif ($type == self::REVIEW) {
            $collection->getSelect()
                ->join(
                    ['order_address' => $this->coreResource->getTableName('sales_order_address')],
                    'main_table.entity_id = order_address.parent_id',
                    ['country_id', 'city', 'region_id']
                )->join(
                    ['order_payment' => $this->coreResource->getTableName('sales_order_payment')],
                    'main_table.entity_id = order_payment.parent_id',
                    ['method']
                )->join(
                    ['quote' => $this->coreResource->getTableName('quote')],
                    'main_table.quote_id = quote.entity_id',
                    ['items_qty']
                )->where('order_address.address_type = ?', 'shipping');
        }

        //process rule on collection according to combination
        $combination = $rule->getCombination();

        // ALL TRUE
        if ($combination == 1) {
            return $this->_processAndCombination(
                $collection,
                $condition,
                $type
            );
        }
        //ANY TRUE
        if ($combination == 2) {
            return $this->processOrCombination($collection, $condition, $type);
        }
    }

    /**
     * process And combination on collection.
     *
     * @param $collection
     * @param $conditions
     * @param $type
     *
     * @return mixed
     */
    public function _processAndCombination($collection, $conditions, $type)
    {
        foreach ($conditions as $condition) {
            $attribute = $condition['attribute'];
            $cond = $condition['conditions'];
            $value = $condition['cvalue'];

            //ignore condition if value is null or empty
            if ($value == '' or $value == null) {
                continue;
            }

            //ignore conditions for already used attribute
            if (in_array($attribute, $this->used)) {
                continue;
            }
            //set used to check later
            $this->used[] = $attribute;

            if ($type == self::REVIEW
                && isset($this->attributeMapForQuote[$attribute])
            ) {
                $attribute = $this->attributeMapForOrder[$attribute];
            } elseif ($type == self::ABANDONED
                && isset($this->attributeMapForOrder[$attribute])
            ) {
                $attribute = $this->attributeMapForQuote[$attribute];
            } else {
                $this->productAttribute[] = $condition;
                continue;
            }

            if ($cond == 'null') {
                if ($value == '1') {
                    $collection->addFieldToFilter(
                        $attribute,
                        ['notnull' => true]
                    );
                } elseif ($value == '0') {
                    $collection->addFieldToFilter(
                        $attribute,
                        [$cond => true]
                    );
                }
            } else {
                if ($cond == 'like' or $cond == 'nlike') {
                    $value = '%' . $value . '%';
                }
                $collection->addFieldToFilter(
                    $attribute,
                    [$this->conditionMap[$cond] => $value]
                );
            }
        }
        return $this->_processProductAttributes($collection);
    }

    /**
     * process Or combination on collection.
     *
     * @param $collection
     * @param $conditions
     * @param $type
     *
     * @return mixed
     */
    public function processOrCombination($collection, $conditions, $type)
    {
        $fieldsConditions = [];
        $multiFieldsConditions = [];
        foreach ($conditions as $condition) {
            $attribute = $condition['attribute'];
            $cond = $condition['conditions'];
            $value = $condition['cvalue'];

            //ignore condition if value is null or empty
            if ($value == '' or $value == null) {
                continue;
            }

            if ($type == self::REVIEW
                && isset($this->attributeMapForQuote[$attribute])
            ) {
                $attribute = $this->attributeMapForOrder[$attribute];
            } elseif ($type == self::ABANDONED
                && isset($this->attributeMapForOrder[$attribute])
            ) {
                $attribute = $this->attributeMapForQuote[$attribute];
            } else {
                $this->productAttribute[] = $condition;
                continue;
            }

            if ($cond == 'null') {
                if ($value == '1') {
                    if (isset($fieldsConditions[$attribute])) {
                        $multiFieldsConditions[$attribute][]
                            = ['notnull' => true];
                        continue;
                    }
                    $fieldsConditions[$attribute] = ['notnull' => true];
                } elseif ($value == '0') {
                    if (isset($fieldsConditions[$attribute])) {
                        $multiFieldsConditions[$attribute][]
                            = [$cond => true];
                        continue;
                    }
                    $fieldsConditions[$attribute] = [$cond => true];
                }
            } else {
                if ($cond == 'like' or $cond == 'nlike') {
                    $value = '%' . $value . '%';
                }
                if (isset($fieldsConditions[$attribute])) {
                    $multiFieldsConditions[$attribute][]
                        = [$this->conditionMap[$cond] => $value];
                    continue;
                }
                $fieldsConditions[$attribute]
                    = [$this->conditionMap[$cond] => $value];
            }
        }
        //all rules condition will be with or combination
        if (!empty($fieldsConditions)) {
            $column = [];
            $cond = [];
            foreach ($fieldsConditions as $key => $fieldsCondition) {
                $column[] = (string)$key;
                $cond[] = $fieldsCondition;
                if (!empty($multiFieldsConditions[$key])) {
                    foreach ($multiFieldsConditions[$key] as $multiFieldsCondition) {
                        $column[] = (string)$key;
                        $cond[] = $multiFieldsCondition;
                    }
                }
            }
            $collection->addFieldToFilter(
                $column,
                $cond
            );
        }
        return $this->_processProductAttributes($collection);
    }

    /**
     * Process product attributes on collection.
     *
     * @param $collection
     *
     * @return mixed
     */
    public function _processProductAttributes($collection)
    {
        //if no product attribute or collection empty return collection
        if (empty($this->productAttribute) or !$collection->getSize()) {
            return $collection;
        }

        foreach ($collection as $collectionItem) {
            $items = $collectionItem->getAllItems();
            foreach ($items as $item) {
                //loaded product
                $product = $item->getProduct();

                //attributes array from loaded product
                $attributes = $this->config->getEntityAttributeCodes(
                    \Magento\Catalog\Model\Product::ENTITY,
                    $product
                );

                foreach ($this->productAttribute as $productAttribute) {
                    $attribute = $productAttribute['attribute'];
                    $cond = $productAttribute['conditions'];
                    $value = $productAttribute['cvalue'];

                    if ($cond == 'null') {
                        if ($value == '0') {
                            $cond = 'neq';
                        } elseif ($value == '1') {
                            $cond = 'eq';
                        }
                        $value = '';
                    }

                    //if attribute is in product's attributes array
                    if (in_array($attribute, $attributes)) {
                        $attr = $this->config->getAttribute('catalog_product', $attribute);
                        //frontend type
                        $frontType = $attr->getFrontend()->getInputType();
                        //if type is select
                        if ($frontType == 'select' or $frontType
                            == 'multiselect'
                        ) {
                            $attributeValue = $product->getAttributeText(
                                $attribute
                            );
                            //evaluate conditions on values. if true then unset item from collection
                            if ($this->_evaluate(
                                $value,
                                $cond,
                                $attributeValue
                            )
                            ) {
                                $collection->removeItemByKey(
                                    $collectionItem->getId()
                                );
                                continue 3;
                            }
                        } else {
                            $getter = 'get';
                            $exploded = explode('_', $attribute);
                            foreach ($exploded as $one) {
                                $getter .= ucfirst($one);
                            }
                            //@codingStandardsIgnoreStart
                            $attributeValue = call_user_func(
                                [$product, $getter]
                            );
                            //@codingStandardsIgnoreEnd
                            //if retrieved value is an array then loop through all array values.
                            // example can be categories
                            if (is_array($attributeValue)) {
                                foreach ($attributeValue as $attrValue) {
                                    //evaluate conditions on values. if true then unset item from collection
                                    if ($this->_evaluate(
                                        $value,
                                        $cond,
                                        $attrValue
                                    )
                                    ) {
                                        $collection->removeItemByKey(
                                            $collectionItem->getId()
                                        );
                                        continue 3;
                                    }
                                }
                            } else {
                                //evaluate conditions on values. if true then unset item from collection
                                if ($this->_evaluate(
                                    $value,
                                    $cond,
                                    $attributeValue
                                )
                                ) {
                                    $collection->removeItemByKey(
                                        $collectionItem->getId()
                                    );
                                    continue 3;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $collection;
    }

    /**
     * Evaluate two values against condition.
     *
     * @param $varOne
     * @param $op
     * @param $varTwo
     *
     * @return bool
     */
    public function _evaluate($varOne, $op, $varTwo)
    {
        switch ($op) {
            case 'eq':
                return $varOne == $varTwo;
            case 'neq':
                return $varOne != $varTwo;
            case 'gteq':
                return $varOne >= $varTwo;
            case 'lteq':
                return $varOne <= $varTwo;
            case 'gt':
                return $varOne > $varTwo;
            case 'lt':
                return $varOne < $varTwo;
        }
    }
}
