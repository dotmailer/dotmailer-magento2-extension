<?php

namespace Dotdigitalgroup\Email\Model\Validator\Schema\Rule;

/**
 * Interface for validation rules that accept parameters
 *
 * Rules implementing this interface can accept parameters from the validation pattern
 * Example: enum:LIST,SEGMENT or min:0
 */
interface ValidatorRuleWithParamsInterface extends ValidatorRuleInterface
{
    /**
     * Set parameters for the rule
     *
     * Receives comma-separated values as an array
     * Each rule implements its own logic to handle the parameters
     *
     * @param array $parameters
     * @return $this
     */
    public function setParameters(array $parameters): ValidatorRuleWithParamsInterface;
}
