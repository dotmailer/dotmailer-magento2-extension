<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

use Dotdigitalgroup\Email\Model\ResourceModel\Cron\Collection;

class Rules extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource.
     *
     * @return null
     */
    public function _construct()
    {
        $this->_init('email_rules', 'id');
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
                    ['quote_address' => $this->_resources->getTableName('quote_address')],
                    'main_table.entity_id = quote_address.quote_id',
                    ['shipping_method', 'country_id', 'city', 'region_id']
                )->joinLeft(
                    ['quote_payment' => $this->_resources->getTableName('quote_payment')],
                    'main_table.entity_id = quote_payment.quote_id',
                    ['method']
                )->where('address_type = ?', 'shipping');
        } elseif ($type == \Dotdigitalgroup\Email\Model\Rules::REVIEW) {
            $collection->getSelect()
                ->join(
                    ['order_address' => $this->_resources->getTableName('sales_order_address')],
                    'main_table.entity_id = order_address.parent_id',
                    ['country_id', 'city', 'region_id']
                )->join(
                    ['order_payment' => $this->_resources->getTableName('sales_order_payment')],
                    'main_table.entity_id = order_payment.parent_id',
                    ['method']
                )->join(
                    ['quote' => $this->_resources->getTableName('quote')],
                    'main_table.quote_id = quote.entity_id',
                    ['items_qty']
                )->where('order_address.address_type = ?', 'shipping');
        }

        return $collection;
    }
}
