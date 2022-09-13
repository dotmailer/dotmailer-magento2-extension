<?php

namespace Dotdigitalgroup\Email\Model;

use Dotdigitalgroup\Email\Model\ResourceModel\Rules\CollectionFactory as RulesCollectionFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Quote\Model\ResourceModel\Quote\Collection as QuoteCollection;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as MageQuoteCollectionFactory;

class Rules extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Exclusion Rule for Abandoned Cart.
     */
    public const ABANDONED = 1;

    /**
     * Exclusion Rule for Product Review.
     */
    public const REVIEW = 2;

    /**
     * Condition combination all.
     */
    public const COMBINATION_TYPE_ALL = 1;

    /**
     * Condition combination any.
     */
    public const COMBINATION_TYPE_ANY = 2;

    /**
     * @var RulesCollectionFactory
     */
    private $rulesCollectionFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var MageQuoteCollectionFactory
     */
    private $quoteCollectionFactory;

    /**
     * @var ResourceModel\Rules
     */
    private $rulesResource;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var int
     */
    private $ruleType;

    /**
     * @var array
     */
    private $conditionMap;

    /**
     * @var array
     */
    private $attributeMapForQuote;

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
     * Rules constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param RulesCollectionFactory $rulesCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param ResourceModel\Rules $rulesResource
     * @param Config $config
     * @param MageQuoteCollectionFactory $quoteCollectionFactory
     * @param SerializerInterface $serializer
     * @param array $data
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     */
    public function __construct(
        Context $context,
        Registry $registry,
        RulesCollectionFactory $rulesCollectionFactory,
        ProductRepositoryInterface $productRepository,
        ResourceModel\Rules $rulesResource,
        Config $config,
        MageQuoteCollectionFactory $quoteCollectionFactory,
        SerializerInterface $serializer,
        array $data = [],
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null
    ) {
        $this->serializer = $serializer;
        $this->config = $config;
        $this->rulesCollectionFactory = $rulesCollectionFactory;
        $this->productRepository = $productRepository;
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
     * @return void
     */
    public function _construct()
    {
        $this->conditionMap = [
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
     * Before save.
     *
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
        return $this->rulesCollectionFactory->create()
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
        return $this->rulesCollectionFactory->create()
            ->getActiveRuleByWebsiteAndType($type, $websiteId);
    }

    /**
     * Process rule on collection.
     *
     * @param OrderCollection|QuoteCollection $collection
     * @param string $type
     * @param int $websiteId
     *
     * @return OrderCollection|QuoteCollection
     */
    public function process($collection, $type, $websiteId)
    {
        $this->ruleType = $type;
        $emailRules = $this->getActiveRuleForWebsite($type, $websiteId);

        //if no rule or condition then return the collection untouched
        if (empty($emailRules)) {
            return $collection;
        }
        try {
            $conditions = $this->serializer->unserialize($emailRules->getConditions());
        } catch (\InvalidArgumentException $e) {
            return $collection;
        }

        if (empty($conditions)) {
            return $collection;
        }

        //process rule on collection according to combination
        $combination = $emailRules->getCombination();
        //join tables to collection according to type
        $collection = $this->rulesResource->joinTablesOnCollectionByType($collection, $type);

        if ($combination == self::COMBINATION_TYPE_ALL) {
            $collection = $this->processAndCombination($collection, $conditions);
        }

        if ($combination == self::COMBINATION_TYPE_ANY) {
            $collection = $this->processOrCombination($collection, $conditions);
        }

        return $collection;
    }

    /**
     * Process And combination on collection.
     *
     * @param OrderCollection|QuoteCollection $collection
     * @param array $ruleConditions
     *
     * @return OrderCollection|QuoteCollection
     */
    public function processAndCombination($collection, $ruleConditions)
    {
        foreach ($ruleConditions as $condition) {
            $attribute = $condition['attribute'];
            $operator = $condition['conditions'];
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

            $collection = $this->processAndCombinationCondition($collection, $operator, $value, $attribute);
        }

        return $this->processProductAttributes($collection);
    }

    /**
     * Process And combination on collection.
     *
     * @param OrderCollection|QuoteCollection $collection
     * @param string $cond
     * @param string $value
     * @param string $attribute
     *
     * @return OrderCollection|QuoteCollection
     */
    private function processAndCombinationCondition($collection, $cond, $value, $attribute)
    {
        $condition = [];

        if ($cond == 'null') {
            if ($value == '1') {
                $condition = ['notnull' => true];
            } elseif ($value == '0') {
                $condition = [$cond => true];
            }
        } else {
            if ($cond == 'like' || $cond == 'nlike') {
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
     * Process Or combination on collection.
     *
     * @param OrderCollection|QuoteCollection $collection
     * @param array $conditions
     *
     * @return OrderCollection|QuoteCollection
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
            if ($value == '' || $value == null) {
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
                if ($cond == 'like' || $cond == 'nlike') {
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
        /**
         * All rule conditions are combined into an array to yield an OR when passed
         * to `addFieldToFilter`. The exception is any 'like' or 'nlike' conditions,
         * which must be added as separate filters in order to have the AND logic.
         */
        if (!empty($fieldsConditions)) {
            $column = $cond = [];
            foreach ($fieldsConditions as $key => $fieldsCondition) {
                $type = key($fieldsCondition);
                if ($type == 'like' || $type == 'nlike') {
                    $collection->addFieldToFilter(
                        (string) $key,
                        $fieldsCondition
                    );
                } else {
                    $column[] = (string) $key;
                    $cond[] = $fieldsCondition;
                }
                if (!empty($multiFieldsConditions[$key])) {
                    foreach ($multiFieldsConditions[$key] as $multiFieldsCondition) {
                        $type = key($multiFieldsCondition);
                        if ($type == 'like' || $type == 'nlike') {
                            $collection->addFieldToFilter(
                                (string) $key,
                                $multiFieldsCondition
                            );
                        } else {
                            $column[] = (string) $key;
                            $cond[] = $multiFieldsCondition;
                        }
                    }
                }
            }
            if (!empty($column) && !empty($cond)) {
                $collection->addFieldToFilter(
                    $column,
                    $cond
                );
            }
        }
        return $this->processProductAttributes($collection);
    }

    /**
     * Process product attributes on collection.
     *
     * @param OrderCollection|QuoteCollection $collection
     *
     * @return OrderCollection|QuoteCollection
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
    private function _evaluate($varOne, $op, $varTwo)
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
     * @param OrderCollection|QuoteCollection $collection
     *
     * @return OrderCollection|QuoteCollection
     */
    private function processProductAttributesInCollection($collection)
    {
        foreach ($collection as $collectionItem) {
            foreach ($collectionItem->getAllItems() as $item) {
                $this->processCollectionItem($collection, $collectionItem->getId(), $item);
            }
        }

        return $collection;
    }

    /**
     * Process collection item.
     *
     * @param QuoteCollection|OrderCollection $collection
     * @param int $collectionItemId
     * @param \Magento\Quote\Model\Quote\Item|\Magento\Sales\Model\Order\Item $item
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function processCollectionItem($collection, $collectionItemId, $item)
    {
        // reload the product to ensure all data is available
        $product = $this->productRepository->getById(
            $item->getProductId(),
            false,
            $item->getStoreId()
        );
        $attributes = $this->getAttributesArrayFromLoadedProduct($product);
        $attributes[] = 'attribute_set_id';

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

            // if attribute is in product's attributes array
            if (!in_array($attribute, $attributes)) {
                continue;
            }

            $attr = $this->config->getAttribute('catalog_product', $attribute);
            $frontendType = $attr->getFrontend()->getInputType();

            if (in_array($frontendType, ['select', 'multiselect'])) {
                /** @var \Magento\Catalog\Model\Product $product */
                $optionId = $product->getData($attribute);

                //evaluate conditions on values. if true then unset item from collection
                if ($this->_evaluate($value, $cond, $optionId)) {
                    $collection->removeItemByKey($collectionItemId);
                    return;
                }
            } else {
                $getter = 'get';
                foreach (explode('_', $attribute) as $one) {
                    $getter .= ucfirst($one);
                }

                $attributeValue = $product->$getter();

                //if retrieved value is an array then loop through all array values.
                // example can be categories
                if (is_array($attributeValue)) {
                    foreach ($attributeValue as $attrValue) {
                        //evaluate conditions on values. if true then unset item from collection
                        if ($this->_evaluate($value, $cond, $attrValue)) {
                            $collection->removeItemByKey($collectionItemId);
                            return;
                        }
                    }
                } else {
                    //evaluate conditions on values. if true then unset item from collection
                    if ($this->_evaluate($value, $cond, $attributeValue)) {
                        $collection->removeItemByKey($collectionItemId);
                        return;
                    }
                }
            }
        }
    }

    /**
     * Get attributes array from loaded product.
     *
     * @param ProductInterface $product
     *
     * @return array
     */
    private function getAttributesArrayFromLoadedProduct($product)
    {
        $attributes = $this->config->getEntityAttributes(
            \Magento\Catalog\Model\Product::ENTITY,
            $product
        );

        return array_keys($attributes);
    }

    /**
     * Filter collection by quote attribute.
     *
     * @param OrderCollection|QuoteCollection $collection
     * @param string $attribute
     * @param array $condition
     * @return OrderCollection|QuoteCollection
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
