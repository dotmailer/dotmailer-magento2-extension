<?php

namespace Dotdigitalgroup\Email\Model\Product\Index;

class Collection extends \Magento\Reports\Model\ResourceModel\Product\Index\Collection\AbstractCollection
{

    /**
     * @return string
     */
    protected function _getTableName()
    {
        return $this->getTable('report_viewed_product_index');
    }
}
