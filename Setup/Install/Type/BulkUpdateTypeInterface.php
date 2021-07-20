<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

interface BulkUpdateTypeInterface
{
    public function fetchRecords();

    public function getUpdateBindings($bind);

    public function getUpdateWhereClause($bind);

    public function getWhereKey();

    public function getBindKey();
}
