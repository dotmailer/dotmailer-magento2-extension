<?php

namespace Dotdigitalgroup\Email\Model\Validator\Schema\Rule;

interface ValidatorRuleInterface
{
    /**
     * SchemaValidator Execute Rule
     *
     * @param mixed $value
     * @return bool
     */
    public function passes($value):bool;
}
