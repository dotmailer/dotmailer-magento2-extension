<?php

namespace Dotdigitalgroup\Email\Model\Sql;

use Laminas\Stdlib\JsonSerializable;

/**
 * Class is wrapper over Zend_Db_Expr for implement JsonSerializable interface.
 */
class Expression extends \Zend_Db_Expr implements JsonSerializable
{
    /**
     * @inheritdoc
     */
    public function jsonSerialize(): mixed
    {
        return [
            'class' => static::class,
            'arguments' => [
                'expression' => $this->_expression,
            ],
        ];
    }
}
