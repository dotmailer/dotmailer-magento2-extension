<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

interface InsertTypeInterface
{
    /**
     * Get the insert fields for this type
     * @return array
     */
    public function getInsertArray();
}