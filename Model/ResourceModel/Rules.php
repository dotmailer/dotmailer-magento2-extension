<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

use Dotdigitalgroup\Email\Model\ResourceModel\Cron\Collection;
use Dotdigitalgroup\Email\Model\Config\Json;
use Dotdigitalgroup\Email\Setup\Schema;

class Rules extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var Json
     */
    protected $serializer;

    /**
     * Initialize resource.
     *
     * @return null
     */
    public function _construct()
    {
        $this->_init(Schema::EMAIL_RULES_TABLE, 'id');
    }

    /**
     * Rules constructor.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param Json $serializer
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        Json $serializer,
        $connectionName = null
    ) {
        $this->serializer = $serializer;
        parent::__construct(
            $context,
            $connectionName
        );
    }

    protected function _afterLoad(\Magento\Framework\Model\AbstractModel $object)
    {
        $object->setCondition($this->serializer->unserialize($object->getConditions()));

        return parent::_afterLoad($object);
    }

    /**
     * Join tables on collection by type.
     *
     * @param Collection $collection
     * @param string $type
     *
     * @return Collection
     */
    public function joinTablesOnCollectionByType($collection, $type)
    {
        if ($type == \Dotdigitalgroup\Email\Model\Rules::ABANDONED) {
            $collection->getSelect()
                ->joinLeft(
                    ['quote_address' => $this->getTable('quote_address')],
                    'main_table.entity_id = quote_address.quote_id',
                    ['shipping_method', 'country_id', 'city', 'region_id']
                )->joinLeft(
                    ['quote_payment' => $this->getTable('quote_payment')],
                    'main_table.entity_id = quote_payment.quote_id',
                    ['method']
                )->where('address_type = ?', 'shipping');
        } elseif ($type == \Dotdigitalgroup\Email\Model\Rules::REVIEW) {
            $collection->getSelect()
                ->join(
                    ['order_address' => $this->getTable('sales_order_address')],
                    'main_table.entity_id = order_address.parent_id',
                    ['country_id', 'city', 'region_id']
                )->join(
                    ['order_payment' => $this->getTable('sales_order_payment')],
                    'main_table.entity_id = order_payment.parent_id',
                    ['method']
                )->where('order_address.address_type = ?', 'shipping');
        }

        return $collection;
    }
}
