<?php

namespace Dotdigitalgroup\Email\Model\Validator\Schema\Rule;

class DateFormatRule implements ValidatorRuleWithParamsInterface
{
    /**
     * @var string
     */
    private $format = "Y-m-d H:i:s";

    /**
     * SchemaValidator Execute Rule
     *
     * @param mixed $value
     * @return bool
     */
    public function passes($value):bool
    {
        $date = \DateTime::createFromFormat($this->format, $value);
        if ($date === false) {
            return false;
        }
        $formattedDate = $date->format($this->format);
        return ($formattedDate === $value);
    }

    /**
     * Set parameters for the rule.
     *
     * @param array $parameters
     * @return ValidatorRuleWithParamsInterface
     */
    public function setParameters(array $parameters): ValidatorRuleWithParamsInterface
    {
        $this->format = array_pop($parameters);
        return $this;
    }
}
