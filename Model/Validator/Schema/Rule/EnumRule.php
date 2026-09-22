<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Validator\Schema\Rule;

/**
 * Enum validation rule
 *
 * Validates that a value is one of the allowed enum values
 */
class EnumRule implements ValidatorRuleWithParamsInterface
{
    /**
     * @var string
     */
    public $key = 'enum';

    /**
     * @var array
     */
    private $allowedValues = [];

    /**
     * Set parameters for enum rule
     *
     * Treats all parameters as allowed enum values
     * Example: enum:LIST,SEGMENT -> ['LIST', 'SEGMENT']
     *
     * @param array $parameters
     * @return $this
     */
    public function setParameters(array $parameters): ValidatorRuleWithParamsInterface
    {
        $this->allowedValues = $parameters;
        return $this;
    }

    /**
     * Assert value is in allowed enum values
     *
     * @param mixed $value
     * @return bool
     */
    public function passes($value): bool
    {
        if (empty($this->allowedValues)) {
            return true;
        }

        return in_array($value, $this->allowedValues, true);
    }
}
