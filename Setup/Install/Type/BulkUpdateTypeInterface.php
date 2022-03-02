<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

interface BulkUpdateTypeInterface
{
    /**
     * Get the bindings for this update
     *
     * @param mixed $bind
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
     * @return mixed
     */
    public function getBindKey();
}
