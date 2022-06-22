<?php

namespace Dotdigitalgroup\Email\Model\Validator\Schema\Rule;

class DateFormatRule implements ValidatorRuleInterface
{
    public const FORMAT = "Y-m-d H:i:s";

    /**
     * SchemaValidator Execute Rule
     *
     * @param mixed $value
     * @return bool
     */
    public function passes($value):bool
    {
        $date = \DateTime::createFromFormat(DateFormatRule::FORMAT, $value);
        $formattedDate = $date->format(DateFormatRule::FORMAT);
        return ($formattedDate === $value);
    }
}
