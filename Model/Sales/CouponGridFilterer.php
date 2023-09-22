<?php

namespace Dotdigitalgroup\Email\Model\Sales;

use Magento\Backend\Block\Widget\Grid\Column\Filter\Select\Extended;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class CouponGridFilterer
{
    /**
     * Callback action for cart price rule coupon grid.
     *
     * @param AbstractCollection $collection
     * @param Extended $column
     * @return void
     */
    public function filterByGeneratedByDotdigital($collection, $column)
    {
        $field = $column->getFilterIndex() ? $column->getFilterIndex()
            : $column->getIndex();
        $value = $column->getFilter()->getValue();
        if ($value == 'null') {
            $collection->addFieldToFilter($field, ['null' => true]);
        } else {
            $collection->addFieldToFilter($field, ['notnull' => true]);
        }
    }

    /**
     * Filter generated for email.
     *
     * @param AbstractCollection $collection
     * @param Extended $column
     */
    public function filterGeneratedForEmail($collection, $column)
    {
        if ($value = $column->getFilter()->getValue()) {
            $collection->addFieldToFilter('email', ['like' => "%$value%"]);
        }
    }
}
