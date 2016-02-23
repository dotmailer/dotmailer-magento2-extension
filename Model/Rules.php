<?php

namespace Dotdigitalgroup\Email\Model;

class Rules extends \Magento\Framework\Model\AbstractModel
{

    const ABANDONED = 1;
    const REVIEW = 2;

    protected $_conditionMap;
    protected $_defaultOptions;
    protected $_attributeMapForQuote;
    protected $_attributeMapForOrder;
    protected $_productAttribute;
    protected $_used = array();

    protected $_objectManager;

    /**
     * constructor
     */
    public function _construct()
    {
        $this->_objectManager
                               = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_defaultOptions = $this->_objectManager->create(
            'Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Type'
        )->defaultOptions();

        $this->_conditionMap         = array(
            'eq'    => 'neq',
            'neq'   => 'eq',
            'gteq'  => 'lteq',
            'lteq'  => 'gteq',
            'gt'    => 'lt',
            'lt'    => 'gt',
            'like'  => 'nlike',
            'nlike' => 'like'
        );
        $this->_attributeMapForQuote = array(
            'method'            => 'method',
            'shipping_method'   => 'shipping_method',
            'country_id'        => 'country_id',
            'city'              => 'city',
            'region_id'         => 'region_id',
            'customer_group_id' => 'main_table.customer_group_id',
            'coupon_code'       => 'main_table.coupon_code',
            'subtotal'          => 'main_table.subtotal',
            'grand_total'       => 'main_table.grand_total',
            'items_qty'         => 'main_table.items_qty',
            'customer_email'    => 'main_table.customer_email',
        );
        $this->_attributeMapForOrder = array(
            'method'            => 'method',
            'shipping_method'   => 'main_table.shipping_method',
            'country_id'        => 'country_id',
            'city'              => 'city',
            'region_id'         => 'region_id',
            'customer_group_id' => 'main_table.customer_group_id',
            'coupon_code'       => 'main_table.coupon_code',
            'subtotal'          => 'main_table.subtotal',
            'grand_total'       => 'main_table.grand_total',
            'items_qty'         => 'items_qty',
            'customer_email'    => 'main_table.customer_email',
        );
        parent::_construct();
        $this->_init('Dotdigitalgroup\Email\Model\Resource\Rules');
    }

    public function beforeSave()
    {
        parent::beforeSave();
        if ($this->isObjectNew()) {
            $this->setCreatedAt(time());
        } else {
            $this->setUpdatedAt(time());
        }
        $this->setCondition(serialize($this->getCondition()));
        $this->setWebsiteIds(implode(',', $this->getWebsiteIds()));

        return $this;
    }

    /**
     * after load
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();
        $this->setCondition(unserialize($this->getCondition()));

        return $this;
    }

    /**
     * check if rule already exist for website
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
            ->addFieldToFilter('type', array('eq' => $type))
            ->addFieldToFilter('website_ids', array('finset' => $websiteId));
        if ($ruleId) {
            $collection->addFieldToFilter('id', array('neq' => $ruleId));
        }
        $collection->setPageSize(1);

        if ($collection->getSize()) {
            return false;
        }

        return true;
    }

    /**
     * @param $type
     * @param $websiteId
     *
     * @return array|\Magento\Framework\DataObject
     */
    public function getActiveRuleForWebsite($type, $websiteId)
    {
        $collection = $this->getCollection();
        $collection
            ->addFieldToFilter('type', array('eq' => $type))
            ->addFieldToFilter('status', array('eq' => 1))
            ->addFieldToFilter('website_ids', array('finset' => $websiteId))
            ->setPageSize(1);
        if ($collection->getSize()) {
            return $collection->getFirstItem();
        }

        return array();
    }

    /**
     * process rule on collection
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
        $condition = unserialize($rule->getCondition());
        if (empty($condition)) {
            return $collection;
        }

        //join tables to collection according to type
        if ($type == self::ABANDONED) {
            $collection->getSelect()
                ->joinLeft(
                    array('quote_address' => 'quote_address'),
                    "main_table.entity_id = quote_address.quote_id",
                    array('shipping_method', 'country_id', 'city', 'region_id')
                )->joinLeft(
                    array('quote_payment' => 'quote_payment'),
                    "main_table.entity_id = quote_payment.quote_id",
                    array('method')
                )->where('address_type = ?', 'shipping');
        } elseif ($type == self::REVIEW) {
            $collection->getSelect()
                ->join(
                    array('order_address' => 'sales_order_address'),
                    "main_table.entity_id = order_address.parent_id",
                    array('country_id', 'city', 'region_id')
                )->join(
                    array('order_payment' => 'sales_order_payment'),
                    "main_table.entity_id = order_payment.parent_id",
                    array('method')
                )->join(
                    array('quote' => 'quote'),
                    "main_table.quote_id = quote.entity_id",
                    array('items_qty')
                )->where('order_address.address_type = ?', 'shipping');
        }

        //process rule on collection according to combination
        $combination = $rule->getCombination();

        // ALL TRUE
        if ($combination == 1) {
            return $this->_processAndCombination(
                $collection, $condition, $type
            );
        }
        //ANY TRUE
        if ($combination == 2) {
            return $this->_processOrCombination($collection, $condition, $type);
        }

    }

    /**
     * process And combination on collection
     *
     * @param $collection
     * @param $conditions
     * @param $type
     *
     * @return mixed
     */
    protected function _processAndCombination($collection, $conditions, $type)
    {
        foreach ($conditions as $condition) {
            $attribute = $condition['attribute'];
            $cond      = $condition['conditions'];
            $value     = $condition['cvalue'];

            //ignore condition if value is null or empty
            if ($value == '' or $value == null) {
                continue;
            }

            //ignore conditions for already used attribute
            if (in_array($attribute, $this->_used)) {
                continue;
            }
            //set used to check later
            $this->_used[] = $attribute;

            if ($type == self::REVIEW
                && isset($this->_attributeMapForQuote[$attribute])
            ) {
                $attribute = $this->_attributeMapForOrder[$attribute];
            } elseif ($type == self::ABANDONED
                && isset($this->_attributeMapForOrder[$attribute])
            ) {
                $attribute = $this->_attributeMapForQuote[$attribute];
            } else {
                $this->_productAttribute[] = $condition;
                continue;
            }

            if ($cond == 'null') {
                if ($value == '1') {
                    $collection->addFieldToFilter(
                        $attribute, array('notnull' => true)
                    );
                } elseif ($value == '0') {
                    $collection->addFieldToFilter(
                        $attribute, array($cond => true)
                    );
                }
            } else {
                if ($cond == 'like' or $cond == 'nlike') {
                    $value = '%' . $value . '%';
                }
                $collection->addFieldToFilter(
                    $attribute, array($this->_conditionMap[$cond] => $value)
                );
            }
        }

        return $this->_processProductAttributes($collection);
    }

    /**
     * process Or combination on collection
     *
     * @param $collection
     * @param $conditions
     * @param $type
     *
     * @return mixed
     */
    protected function _processOrCombination($collection, $conditions, $type)
    {
        $fieldsConditions      = array();
        $multiFieldsConditions = array();
        foreach ($conditions as $condition) {
            $attribute = $condition['attribute'];
            $cond      = $condition['conditions'];
            $value     = $condition['cvalue'];

            //ignore condition if value is null or empty
            if ($value == '' or $value == null) {
                continue;
            }

            if ($type == self::REVIEW
                && isset($this->_attributeMapForQuote[$attribute])
            ) {
                $attribute = $this->_attributeMapForOrder[$attribute];
            } elseif ($type == self::ABANDONED
                && isset($this->_attributeMapForOrder[$attribute])
            ) {
                $attribute = $this->_attributeMapForQuote[$attribute];
            } else {
                $this->_productAttribute[] = $condition;
                continue;
            }

            if ($cond == 'null') {
                if ($value == '1') {
                    if (isset($fieldsConditions[$attribute])) {
                        $multiFieldsConditions[$attribute]
                            = array('notnull' => true);
                        continue;
                    }
                    $fieldsConditions[$attribute] = array('notnull' => true);
                } elseif ($value == '0') {
                    if (isset($fieldsConditions[$attribute])) {
                        $multiFieldsConditions[$attribute]
                            = array($cond => true);;
                        continue;
                    }
                    $fieldsConditions[$attribute] = array($cond => true);
                }
            } else {
                if ($cond == 'like' or $cond == 'nlike') {
                    $value = '%' . $value . '%';
                }
                if (isset($fieldsConditions[$attribute])) {
                    $multiFieldsConditions[$attribute]
                        = array($this->_conditionMap[$cond] => $value);
                    continue;
                }
                $fieldsConditions[$attribute]
                    = array($this->_conditionMap[$cond] => $value);
            }
        }
        //all rules condition will be with or combination
        if ( ! empty($fieldsConditions)) {
            $column = array();
            $cond   = array();
            foreach ($fieldsConditions as $key => $fieldsCondition) {
                $column[] = $key;
                $cond[]   = $fieldsCondition;
            }
            if ( ! empty($multiFieldsConditions)) {
                foreach (
                    $multiFieldsConditions as $key => $multiFieldsCondition
                ) {
                    if (in_array($key, $column)) {
                        $column[] = $key;
                        $cond[]   = $multiFieldsCondition;
                        continue;
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
     * process product attributes on collection
     *
     * @param $collection
     *
     * @return mixed
     */
    protected function _processProductAttributes($collection)
    {
        //if no product attribute or collection empty return collection
        if (empty($this->_productAttribute) or ! $collection->getSize()) {
            return $collection;
        }

        $productModel = $this->_objectManager->create(
            'Magento\Catalog\Model\Product'
        );
        foreach ($collection as $collectionItem) {
            $items = $collectionItem->getAllItems();
            foreach ($items as $item) {
                $productId = $item->getProductId();
                //loaded product
                $product = $productModel
                    ->setStoreId($item->getStoreId())
                    ->load($productId);

                //attributes array from loaded product
                $attributes = $this->_objectManager->create(
                    'Magento\Eav\Model\Config'
                )->getEntityAttributeCodes(
                    \Magento\Catalog\Model\Product::ENTITY,
                    $product
                );

                foreach ($this->_productAttribute as $productAttribute) {
                    $attribute = $productAttribute['attribute'];
                    $cond      = $productAttribute['conditions'];
                    $value     = $productAttribute['cvalue'];

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
                        $attr = $this->_objectManager->get(
                            'Magento\Eav\Model\Config'
                        )
                            ->getAttribute('catalog_product', $attribute);
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
                                $value, $cond, $attributeValue
                            )
                            ) {
                                $collection->removeItemByKey(
                                    $collectionItem->getId()
                                );
                                continue 3;
                            }
                        } else {
                            $getter   = 'get';
                            $exploded = explode('_', $attribute);
                            foreach ($exploded as $one) {
                                $getter .= ucfirst($one);
                            }
                            $attributeValue = call_user_func(
                                array($product, $getter)
                            );
                            //if retrieved value is an array then loop through all array values. example can be categories
                            if (is_array($attributeValue)) {
                                foreach ($attributeValue as $attrValue) {
                                    //evaluate conditions on values. if true then unset item from collection
                                    if ($this->_evaluate(
                                        $value, $cond, $attrValue
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
                                    $value, $cond, $attributeValue
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
     * evaluate two values against condition
     *
     * @param $var1
     * @param $op
     * @param $var2
     *
     * @return bool
     */
    protected function _evaluate($var1, $op, $var2)
    {
        switch ($op) {
            case "eq":
                return $var1 == $var2;
            case "neq":
                return $var1 != $var2;
            case "gteq":
                return $var1 >= $var2;
            case "lteq":
                return $var1 <= $var2;
            case "gt":
                return $var1 > $var2;
            case "lt":
                return $var1 < $var2;
        }
    }
}