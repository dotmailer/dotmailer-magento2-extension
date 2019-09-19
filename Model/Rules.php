<?php

namespace Dotdigitalgroup\Email\Model;

use Dotdigitalgroup\Email\Model\Config\Json;

class Rules extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Exclusion Rule for Abandoned Cart.
     */
    const ABANDONED = 1;

    /**
     * Exclusion Rule for Product Review.
     */
    const REVIEW = 2;

    /**
     * Condition combination all.
     */
    const COMBINATION_TYPE_ALL = 1;

    /**
     * Condition combination any.
     */
    const COMBINATION_TYPE_ANY = 2;

    /**
     * @var int
     */
    public $ruleType;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory
     */
    private $quoteCollectionFactory;

    /**
     * @var ResourceModel\Rules
     */
    private $rulesResource;

    /**
     * @var array
     */
    private $conditionMap;

    /**
     * @var array
     */
    private $defaultOptions;

    /**
     * @var array
     */
    public $attributeMapForQuote;

    /**
     * @var array
     */
    private $attributeMapForOrder;

    /**
     * @var array
     */
    private $productAttribute;

    /**
     * @var array
     */
    private $used = [];

    /**
     * @var Adminhtml\Source\Rules\Type
     */
    private $rulesType;

    /**
     * @var \Magento\Eav\Model\Config
     */
    private $config;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * Rules constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory
     * @param Adminhtml\Source\Rules\Type $rulesType
     * @param \Magento\Eav\Model\Config $config
     * @param Json $serializer
     * @param ResourceModel\Rules $rulesResource
     * @param array $data
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory,
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Type $rulesType,
        \Magento\Eav\Model\Config $config,
        \Dotdigitalgroup\Email\Model\Config\Json $serializer,
        \Dotdigitalgroup\Email\Model\ResourceModel\Rules $rulesResource,
        array $data = [],
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null
    ) {
        $this->serializer = $serializer;
        $this->config       = $config;
        $this->rulesType    = $rulesType;
        $this->rulesResource = $rulesResource;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
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
     *
     * @return null
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
        $this->_init(\Dotdigitalgroup\Email\Model\ResourceModel\Rules::class);
    }

    /**
     * @return $this
     */
    public function beforeSave()
    {
        parent::beforeSave();
        if ($this->isObjectNew()) {
            $this->setCreatedAt(time());
        } else {
            $this->setUpdatedAt(time());
        }
        $this->setConditions($this->serializer->serialize($this->getConditions()));
        $this->setWebsiteIds(implode(',', $this->getWebsiteIds()));
        return $this;
    }

    /**
     * Check if rule already exist for website.
     *
     * @param int $websiteId
     * @param string $type
     * @param bool $ruleId
     *
     * @return bool
     */
    public function checkWebsiteBeforeSave($websiteId, $type, $ruleId = false)
    {
        return $this->getCollection()
            ->hasCollectionAnyItemsByWebsiteAndType($websiteId, $type, $ruleId);
    }

    /**
     * Get rule for website.
     *
     * @param string $type
     * @param int $websiteId
     *
     * @return array|\Dotdigitalgroup\Email\Model\Rules
     */
    public function getActiveRuleForWebsite($type, $websiteId)
    {
        return $this->getCollection()
            ->getActiveRuleByWebsiteAndType($type, $websiteId);
    }

    /**
     * Process rule on collection.
     *
     * @param \Magento\Sales\Model\ResourceModel\Order\Collection|
     * \Magento\Quote\Model\ResourceModel\Quote\Collection $collection
     * @param string $type
     * @param int $websiteId
     *
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection|
     * \Magento\Quote\Model\ResourceModel\Quote\Collection $collection
     */
    public function process($collection, $type, $websiteId)
    {
        $this->ruleType = $type;
        $emailRules = $this->getActiveRuleForWebsite($type, $websiteId);

        //if no rule or condition then return the collection untouched
        if (empty($emailRules)) {
            return $collection;
        }
        $condition = $this->serializer->unserialize($emailRules->getConditions());
        if (empty($condition)) {
            return $collection;
        }

        //process rule on collection according to combination
        $combination = $emailRules->getCombination();
        //join tables to collection according to type
        $collection = $this->rulesResource->joinTablesOnCollectionByType($collection, $type);

        if ($combination == self::COMBINATION_TYPE_ALL) {
            $collection = $this->processAndCombination($collection, $condition);
        }

        if ($combination == self::COMBINATION_TYPE_ANY) {
            $collection = $this->processOrCombination($collection, $condition);
        }

        return $collection;
    }

    /**
     * Process And combination on collection.
     *
     * @param \Magento\Sales\Model\ResourceModel\Order\Collection|
     * \Magento\Quote\Model\ResourceModel\Quote\Collectio $collection
     * @param array $conditions
     *
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection|
     * \Magento\Quote\Model\ResourceModel\Quote\Collection $collection
     */
    public function processAndCombination($collection, $conditions)
    {
        foreach ($conditions as $condition) {
            $attribute = $condition['attribute'];
            $cond = $condition['conditions'];
            $value = $condition['cvalue'];

            //ignore condition if value is null or empty
            if ($value == '' || $value == null) {
                continue;
            }

            //ignore conditions for already used attribute
            if (in_array($attribute, $this->used)) {
                continue;
            }
            //set used to check later
            $this->used[] = $attribute;

            //product review
            if ($this->ruleType == self::REVIEW && isset($this->attributeMapForQuote[$attribute])) {
                $attribute = $this->attributeMapForOrder[$attribute];
                //abandoned cart
            } elseif ($this->ruleType == self::ABANDONED && isset($this->attributeMapForOrder[$attribute])) {
                $attribute = $this->attributeMapForQuote[$attribute];
            } else {
                $this->productAttribute[] = $condition;
                continue;
            }

            $collection = $this->processProcessAndCombinationCondition($collection, $cond, $value, $attribute);
        }

        return $this->processProductAttributes($collection);
    }

    /**
     * @param \Magento\Sales\Model\ResourceModel\Order\Collection|
     * \Magento\Quote\Model\ResourceModel\Quote\Collection $collection
     * @param string $cond
     * @param string $value
     * @param string $attribute
     *
     * @return null
     */
    private function processProcessAndCombinationCondition($collection, $cond, $value, $attribute)
    {
        if ($cond == 'null') {
            if ($value == '1') {
                $condition = ['notnull' => true];
            } elseif ($value == '0') {
                $condition = [$cond => true];
            }
        } else {
            if ($cond == 'like' or $cond == 'nlike') {
                $value = '%' . $value . '%';
            }
            //condition with null values can't be filtered using string, include to filter null values
            $conditionMap = [$this->conditionMap[$cond] => $value];
            if ($cond == 'eq' || $cond == 'neq') {
                $conditionMap[] = ['null' => true];
            }

            $condition = $conditionMap;
        }

        //filter by quote attribute
        if ($attribute == 'items_qty' && $this->ruleType == self::REVIEW) {
            $collection = $this->filterCollectionByQuoteAttribute($collection, $attribute, $condition);
        } else {
            $collection->addFieldToFilter($attribute, $condition);
        }

        return $collection;
    }

    /**
     * process Or combination on collection.
     *
     * @param \Magento\Sales\Model\ResourceModel\Order\Collection|
     * \Magento\Quote\Model\ResourceModel\Quote\Collectio $collection
     * @param array $conditions
     * @param string $type
     *
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection|
     * \Magento\Quote\Model\ResourceModel\Quote\Collection $collection
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function processOrCombination($collection, $conditions)
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

            if ($this->ruleType == self::REVIEW && isset($this->attributeMapForQuote[$attribute])) {
                $attribute = $this->attributeMapForOrder[$attribute];
            } elseif ($this->ruleType == self::ABANDONED && isset($this->attributeMapForOrder[$attribute])) {
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
            $column = $cond = [];
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
        return $this->processProductAttributes($collection);
    }

    /**
     * Process product attributes on collection.
     *
     * @param \Magento\Sales\Model\ResourceModel\Order\Collection|
     * \Magento\Quote\Model\ResourceModel\Quote\Collectio $collection
     *
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection|
     * \Magento\Quote\Model\ResourceModel\Quote\Collection $collection
     */
    private function processProductAttributes($collection)
    {
        //if no product attribute or collection empty return collection
        if (empty($this->productAttribute) || !$collection->getSize()) {
            return $collection;
        }

        $collection = $this->processProductAttributesInCollection($collection);

        return $collection;
    }

    /**
     * Evaluate two values against condition.
     *
     * @param string $varOne
     * @param string $op
     * @param string $varTwo
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

        return false;
    }

    /**
     * Process product attributes on collection.
     *
     * @param \Magento\Sales\Model\ResourceModel\Order\Collection|
     * \Magento\Quote\Model\ResourceModel\Quote\Collectio $collection
     *
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection|
     * \Magento\Quote\Model\ResourceModel\Quote\Collection $collection
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function processProductAttributesInCollection($collection)
    {
        foreach ($collection as $collectionItem) {
            $items = $collectionItem->getAllItems();
            foreach ($items as $item) {
                $product = $item->getProduct();
                $attributes = $this->getAttributesArrayFromLoadedProduct($product);

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
                            $attributeValue = call_user_func(
                                [$product, $getter]
                            );
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
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return array
     */
    private function getAttributesArrayFromLoadedProduct($product)
    {
        //attributes array from loaded product
        $attributes = $this->config->getEntityAttributes(
            \Magento\Catalog\Model\Product::ENTITY,
            $product
        );

        return array_keys($attributes);
    }

    /**
     * @param \Magento\Sales\Model\ResourceModel\Order\Collection $collection
     * @param string $attribute
     * @param array $condition
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    private function filterCollectionByQuoteAttribute($collection, $attribute, array $condition)
    {
        $originalCollection = clone $collection;
        $quoteCollection = $this->quoteCollectionFactory->create();
        $quoteIds = $originalCollection->getColumnValues('quote_id');
        if ($quoteIds) {
            $quoteCollection->addFieldToFilter('entity_id', ['in' => $quoteIds])
                ->addFieldToFilter($attribute, $condition);
            //no need for empty check - because should include the null result, it should work like exclusion filter!
            $collection->addFieldToFilter('quote_id', ['in' => $quoteCollection->getAllIds()]);
        }

        return $collection;
    }
}
