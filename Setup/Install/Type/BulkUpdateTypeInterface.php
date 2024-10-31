<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

interface BulkUpdateTypeInterface
{
    /**
     * Get the bindings for this update
     *
     * @param array $bind
     *
     * @return mixed
     */
    public function getUpdateBindings($bind);

    /**
     * Get the where clause for this update
     *
     * @param array $row
     *
     * @return mixed
     */
    public function getUpdateWhereClause($row);

    /**
     * Get the key for the update clause
     *
     * @deprecated This shouldn't be part of the interface because it doesn't fit
     * all migration scenarios. e.g. some bulk update migrations need to update more
     * than one column-value pair, so getUpdateBindings() needs to receive an array.
     * @see getUpdateBindings
     *
     * @return mixed
     */
    public function getBindKey();
}
