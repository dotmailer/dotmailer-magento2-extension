<?php

namespace Dotdigitalgroup\Email\Model\Sales;

class CouponGridFilterer
{
    /**
     * Callback action for cart price rule coupon grid.
     *
     * @param AbstractCollection $collection
     * @param Column $column
     * @return void
     */
    public function filterByGeneratedByDotmailer($collection, $column)
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
}
