<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Validator\Schema\Rule;

/**
 * Minimum value validation rule
 *
 * Validates that a numeric value is greater than or equal to a minimum
 */
class MinRule implements ValidatorRuleWithParamsInterface
{
    /**
     * @var string
     */
    public $key = 'min';

    /**
     * @var int|float|null
     */
    private $minValue;

    /**
     * Set parameters for min rule
     *
     * Takes the first parameter as the minimum value
     * Example: min:0 -> [0]
     *          min:1 -> [1]
     *
     * @param array $parameters
     * @return $this
     */
    public function setParameters(array $parameters): ValidatorRuleWithParamsInterface
    {
        if (!empty($parameters)) {
            $this->minValue = (int) $parameters[0];
        }
        return $this;
    }

    /**
     * Assert value is >= minimum
     *
     * @param mixed $value
     * @return bool
     */
    public function passes($value): bool
    {
        if ($this->minValue === null) {
            return true;
        }

        if (!is_numeric($value)) {
            return false;
        }

        return $value >= $this->minValue;
    }
}
