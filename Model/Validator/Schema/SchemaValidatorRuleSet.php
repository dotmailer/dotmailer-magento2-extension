<?php

namespace Dotdigitalgroup\Email\Model\Validator\Schema;

use Dotdigitalgroup\Email\Model\Validator\Schema\Exception\PatternInvalidException;
use Dotdigitalgroup\Email\Model\Validator\Schema\Exception\RuleNotDefinedException;

class SchemaValidatorRuleSet implements SchemaValidatorRuleSetInterface
{
    /**
     * @var array
     */
    public $rules = [];

    /**
     * @var bool
     */
    public $processed = false;

    /**
     * @var SchemaValidatorRuleFactory
     */
    private $schemaValidatorRuleFactory;

    /**
     * Construct SchemaValidatorRuleSet
     *
     * @param SchemaValidatorRuleFactory $schemaValidatorRuleFactory
     */
    public function __construct(
        SchemaValidatorRuleFactory $schemaValidatorRuleFactory
    ) {
        $this->schemaValidatorRuleFactory = $schemaValidatorRuleFactory;
    }

    /**
     * Mark ruleset as processed
     *
     * @return static
     */
    public function hasProcessed(): SchemaValidatorRuleSetInterface
    {
        $this->processed = true;
        return $this;
    }

    /**
     * Set rules for structure key
     *
     * @param string $rulePattern
     * @return void
     */
    public function processRulePattern(string $rulePattern)
    {
        foreach (explode(' ', ltrim($rulePattern, ':')) as $rulePattern) {
            $this->rules[] = $this->schemaValidatorRuleFactory->create(["pattern" => $rulePattern]);
        }
    }
}
