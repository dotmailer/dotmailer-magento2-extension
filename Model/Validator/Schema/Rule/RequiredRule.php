<?php

namespace Dotdigitalgroup\Email\Model\Validator\Schema\Rule;

class RequiredRule implements ValidatorRuleInterface
{
    /**
     * SchemaValidator Execute Rule,
     *
     * (int) 0 and (float) 0.00 will be evaluated as int/float
     * and pass required test
     *
     * @param mixed $value
     * @return bool
     */
    public function passes($value):bool
    {
        return !empty($value) || is_int($value) || is_float($value);
    }
}
