<?php

namespace Dotdigitalgroup\Email\Model\Validator\Schema;

use Dotdigitalgroup\Email\Model\Validator\Schema\Exception\PatternInvalidException;
use Dotdigitalgroup\Email\Model\Validator\Schema\Exception\RuleNotDefinedException;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\ValidatorRuleInterface;

interface SchemaValidatorRuleInterface
{
    /**
     * Get and validate rule
     *
     * @param string $key
     * @return ValidatorRuleInterface
     * @throws PatternInvalidException
     * @throws RuleNotDefinedException
     */
    public function setRule(string $key):ValidatorRuleInterface;

    /**
     * Run rule
     *
     * @param mixed $validatableValue
     * @return bool
     */
    public function assert($validatableValue): bool;
}
