<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

use Magento\Framework\DB\Select;

interface UpdateTypeInterface
{
    /**
     * Get the bindings for this update
     *
     * @return array
     */
    public function getUpdateBindings();

    /**
     * Get the where clause for this update
     *
     * @param Select $selectStatement
     *
     * @return array
     */
    public function getUpdateWhereClause(Select $selectStatement);
}
