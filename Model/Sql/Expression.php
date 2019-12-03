<?php

namespace Dotdigitalgroup\Email\Model\Sql;

use Zend\Stdlib\JsonSerializable;

/**
 * Class is wrapper over Zend_Db_Expr for implement JsonSerializable interface.
 */
class Expression extends \Zend_Db_Expr implements JsonSerializable
{
    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return [
            'class' => static::class,
            'arguments' => [
                'expression' => $this->_expression,
            ],
        ];
    }
}
