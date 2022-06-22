<?php

namespace Dotdigitalgroup\Email\Model\Validator\Schema;

use Dotdigitalgroup\Email\Model\Validator\Schema\Exception\PatternInvalidException;
use Dotdigitalgroup\Email\Model\Validator\Schema\Exception\RuleNotDefinedException;

/**
 * @property string $key
 */
interface SchemaValidatorRuleSetInterface
{
    /**
     * Mark ruleset as processed
     *
     * @return static
     */
    public function hasProcessed(): SchemaValidatorRuleSetInterface;

    /**
     * Set rules for structure key
     *
     * @param string $rulePattern
     * @return void
     * @throws PatternInvalidException
     * @throws RuleNotDefinedException
     */
    public function processRulePattern(string $rulePattern);
}
