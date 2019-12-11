<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

interface UpdateTypeInterface
{
    /**
     * Get the bindings for this update
     * @return array
     */
    public function getUpdateBindings();

    /**
     * Get the where clause for this update
     * @return array
     */
    public function getUpdateWhereClause();
}
