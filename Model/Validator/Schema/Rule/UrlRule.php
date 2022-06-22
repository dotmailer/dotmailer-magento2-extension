<?php

namespace Dotdigitalgroup\Email\Model\Validator\Schema\Rule;

use Zend_Uri;

class UrlRule implements ValidatorRuleInterface
{
    /**
     * SchemaValidator Execute Rule
     *
     * @param mixed $value
     * @return bool
     */
    public function passes($value):bool
    {
        return Zend_Uri::check($value);
    }
}
