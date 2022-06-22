<?php

namespace Dotdigitalgroup\Email\Model\Validator\Schema;

use Dotdigitalgroup\Email\Model\Validator\Schema\Exception\PatternInvalidException;
use Dotdigitalgroup\Email\Model\Validator\Schema\Exception\RuleNotDefinedException;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\DateFormatRuleFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\IsFloatRuleFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\IsIntRuleFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\IsStringRuleFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\RequiredRuleFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\UrlRuleFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\ValidatorRuleInterface;

class SchemaValidatorRule implements SchemaValidatorRuleInterface
{

    /**
     * @var mixed|string
     */
    public $key;

    /**
     * @var ValidatorRuleInterface
     */
    private $rule;

    /**
     * @var DateFormatRuleFactory
     */
    private $dateFormatRuleFactory;

    /**
     * @var IsFloatRuleFactory
     */
    private $isFloatRuleFactory;

    /**
     * @var IsIntRuleFactory
     */
    private $isIntRuleFactory;

    /**
     * @var IsStringRuleFactory
     */
    private $isStringRuleFactory;

    /**
     * @var RequiredRuleFactory
     */
    private $requiredRuleFactory;

    /**
     * @var UrlRuleFactory
     */
    private $urlRuleFactory;

    /**
     * Construct SchemaValidatorRule
     *
     * @param DateFormatRuleFactory $dateFormatRuleFactory
     * @param IsFloatRuleFactory $isFloatRuleFactory
     * @param IsIntRuleFactory $isIntRuleFactory
     * @param IsStringRuleFactory $isStringRuleFactory
     * @param RequiredRuleFactory $requiredRuleFactory
     * @param UrlRuleFactory $urlRuleFactory
     * @param string $pattern
     * @throws RuleNotDefinedException
     */
    public function __construct(
        DateFormatRuleFactory $dateFormatRuleFactory,
        IsFloatRuleFactory $isFloatRuleFactory,
        IsIntRuleFactory $isIntRuleFactory,
        IsStringRuleFactory $isStringRuleFactory,
        RequiredRuleFactory $requiredRuleFactory,
        UrlRuleFactory $urlRuleFactory,
        string $pattern
    ) {
        $this->dateFormatRuleFactory = $dateFormatRuleFactory;
        $this->isFloatRuleFactory = $isFloatRuleFactory;
        $this->isIntRuleFactory = $isIntRuleFactory;
        $this->isStringRuleFactory = $isStringRuleFactory;
        $this->requiredRuleFactory = $requiredRuleFactory;
        $this->urlRuleFactory = $urlRuleFactory;
        $this->key  = $pattern;
        $this->rule = $this->setRule($pattern);
    }

    /**
     * Get and validate rule
     *
     * @param string $key
     * @return ValidatorRuleInterface
     * @throws RuleNotDefinedException
     */
    public function setRule(string $key):ValidatorRuleInterface
    {
        $ruleFactoryKey = "{$key}RuleFactory";

        if (!property_exists($this, $ruleFactoryKey)) {
            throw new RuleNotDefinedException(
                sprintf('Undefined validation rule: %s', $ruleFactoryKey)
            );
        }

        if (!method_exists($this->{$ruleFactoryKey}, 'create')) {
            throw new RuleNotDefinedException(
                sprintf('Rule is not a valid Magento2 factory: %s', $ruleFactoryKey)
            );
        }

        return $this->{$ruleFactoryKey}->create();
    }

    /**
     * Run rule
     *
     * @param mixed $validatableValue
     * @return bool
     */
    public function assert($validatableValue): bool
    {
        return $this->rule->passes($validatableValue);
    }
}
