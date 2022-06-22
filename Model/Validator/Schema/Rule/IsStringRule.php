<?php

namespace Dotdigitalgroup\Email\Model\Validator\Schema\Rule;

class IsStringRule implements ValidatorRuleInterface
{
    /**
     * SchemaValidator Execute Rule
     *
     * @param mixed $value
     * @return bool
     */
    public function passes($value):bool
    {
        return is_string($value);
    }
}
