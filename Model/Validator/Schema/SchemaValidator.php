<?php

namespace Dotdigitalgroup\Email\Model\Validator\Schema;

use Dotdigitalgroup\Email\Model\Validator\Schema\Exception\PatternInvalidException;
use Dotdigitalgroup\Email\Model\Validator\Schema\Exception\RuleNotDefinedException;

class SchemaValidator implements SchemaValidatorInterface
{
    /**
     * Errors reported
     *
     * @var array
     */
    private $errors = [];

    /**
     * Pattern string
     *
     * @var string
     */
    private $pattern;

    /**
     * Pattern mapped rules
     *
     * @var array
     */
    private $patternRules;

    /**
     * @var SchemaValidatorRuleSetFactory
     */
    private $schemaValidatorRuleSetFactory;

    /**
     * Construct SchemaValidator
     *
     * @param SchemaValidatorRuleSetFactory $schemaValidatorRuleSetFactory
     * @param array $pattern
     * @throws PatternInvalidException
     * @throws RuleNotDefinedException
     */
    public function __construct(
        SchemaValidatorRuleSetFactory $schemaValidatorRuleSetFactory,
        array $pattern = []
    ) {
        $this->schemaValidatorRuleSetFactory = $schemaValidatorRuleSetFactory;
        $this->setPattern($pattern);
    }

    /**
     * Get validation errors
     *
     * @return array
     */
    public function getErrors():array
    {
        return $this->errors;
    }

    /**
     * Insure pattern is traversable
     *
     * @param array $pattern
     * @return void
     * @throws PatternInvalidException
     * @throws RuleNotDefinedException
     */
    public function setPattern(array $pattern)
    {
        $this->pattern = $pattern;
        $this->patternRules = $this->buildRuleSetsFromPattern();
    }

    /**
     * Returns true if $value matches $pattern
     *
     * @param array $validatableStructure
     * @return bool
     */
    public function isValid(array $validatableStructure): bool
    {
        $this->matchValidatablePattern($validatableStructure);
        $unprocessedRules = array_filter($this->patternRules, [$this,'getUnprocessedRules']);
        if (count($unprocessedRules) > 0) {
            foreach ($unprocessedRules as $ruleKey => $ruleSet) {
                $this->errors[$ruleKey] = sprintf(
                    'Key [%s] is required in array structure',
                    $ruleKey
                );
            }

        }
        return empty($this->errors);
    }

    /**
     * Construct pattern rule sets
     *
     * @param array|null $pattern
     * @param string|null $patternParentKey
     * @param array $ruleSets
     * @return array
     * @throws PatternInvalidException
     * @throws RuleNotDefinedException
     */
    private function buildRuleSetsFromPattern(
        ?array $pattern = null,
        ?string $patternParentKey = null,
        array $ruleSets = []
    ): array {
        if ($pattern === null) {
            $pattern = $this->pattern;
        }
        foreach ($pattern as $patternKey => $patternValue) {

            if ($patternParentKey !== null) {
                $patternKey = "{$patternParentKey}.{$patternKey}";
            }
            $ruleSet = $this->schemaValidatorRuleSetFactory->create(["key" => $patternKey]);
            $ruleSets[$patternKey] = $ruleSet;
            if (is_array($patternValue)) {
                return $this->buildRuleSetsFromPattern($patternValue, $patternKey, $ruleSets);
            }
            $ruleSet->processRulePattern($patternValue);
        }

        return $ruleSets;
    }

    /**
     * Match pattern to validation rule set
     *
     * @param array $validatableStructure
     * @param string|null $validatableParentKey
     * @return bool
     */
    private function matchValidatablePattern(array $validatableStructure, ?string $validatableParentKey = null): bool
    {
        $validationSuccessful = true;
        foreach ($validatableStructure as $validatableKey => $validatableValue) {

            if ($validatableParentKey !== null) {
                if (is_int($validatableKey)) {
                    $validatableKey = '*';
                }
                $validatableKey = "{$validatableParentKey}.{$validatableKey}";
            }

            if (is_array($validatableValue)) {
                $validationSuccessful = $this->matchValidatablePattern($validatableValue, $validatableKey);
            }

            if (array_key_exists($validatableKey, $this->patternRules)) {
                $ruleSet = $this->getRuleSet($validatableKey);
                $ruleSet->hasProcessed();

                if ($this->skipNullable($ruleSet, $validatableValue)) {
                    continue;
                }

                foreach ($ruleSet->rules as $rule) {
                    if (empty($rule)) {
                        continue;
                    }
                    if (!$rule->assert($validatableValue)) {
                        $this->errors[$validatableKey] = sprintf(
                            'Validation rule %s failed to assert value %s',
                            $rule->key,
                            json_encode($validatableValue)
                        );
                        break;
                    }
                }
            }
        }

        return $validationSuccessful;
    }

    /**
     * Get rule set by validatable structure key
     *
     * @param string $validatableKey
     * @return SchemaValidatorRuleSet
     */
    private function getRuleSet(string $validatableKey):SchemaValidatorRuleSet
    {
        return $this->patternRules[$validatableKey];
    }

    /**
     * Whether an empty/null value should skip all other rules for its key.
     *
     * Purely additive: only applies when the pattern explicitly includes the
     * `nullable` rule (e.g. 'nullable|dateFormat:Y-m-d'). Every other pattern,
     * with no `nullable` rule present, is unaffected and behaves exactly as
     * before — empty values still fail their assigned rules as normal.
     *
     * @param SchemaValidatorRuleSet $ruleSet
     * @param mixed $validatableValue
     * @return bool
     */
    private function skipNullable(SchemaValidatorRuleSet $ruleSet, $validatableValue): bool
    {
        if ($validatableValue !== null && $validatableValue !== '') {
            return false;
        }

        foreach ($ruleSet->rules as $rule) {
            if (!empty($rule) && $rule->key === 'nullable') {
                return true;
            }
        }

        return false;
    }

    /**
     * Insure all pattern keys have been processed
     *
     * @param SchemaValidatorRuleSet $ruleSet
     * @return bool
     */
    private function getUnprocessedRules(SchemaValidatorRuleSet $ruleSet): bool
    {
        return !$ruleSet->processed;
    }
}
