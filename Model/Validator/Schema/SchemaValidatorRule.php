<?php

namespace Dotdigitalgroup\Email\Model\Validator\Schema;

use Dotdigitalgroup\Email\Model\Validator\Schema\Exception\RuleNotDefinedException;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\DateFormatAtomRuleFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\DateFormatRuleFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\EnumRuleFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\IsFloatRuleFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\IsIntRuleFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\IsStringRuleFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\MinRuleFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\NullableRuleFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\RequiredRuleFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\UrlRuleFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\ValidatorRuleInterface;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\ValidatorRuleWithParamsInterface;

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
     * @var DateFormatAtomRuleFactory
     */
    private $dateFormatAtomRuleFactory;

    /**
     * @var DateFormatRuleFactory
     */
    private $dateFormatRuleFactory;

    /**
     * @var EnumRuleFactory
     */
    private $enumRuleFactory;

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
     * @var MinRuleFactory
     */
    private $minRuleFactory;

    /**
     * @var NullableRuleFactory
     */
    private $nullableRuleFactory;

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
     * @param DateFormatAtomRuleFactory $dateFormatAtomRuleFactory
     * @param DateFormatRuleFactory $dateFormatRuleFactory
     * @param EnumRuleFactory $enumRuleFactory
     * @param IsFloatRuleFactory $isFloatRuleFactory
     * @param IsIntRuleFactory $isIntRuleFactory
     * @param IsStringRuleFactory $isStringRuleFactory
     * @param MinRuleFactory $minRuleFactory
     * @param NullableRuleFactory $nullableRuleFactory
     * @param RequiredRuleFactory $requiredRuleFactory
     * @param UrlRuleFactory $urlRuleFactory
     * @param string $pattern
     *
     * @throws RuleNotDefinedException
     */
    public function __construct(
        DateFormatAtomRuleFactory $dateFormatAtomRuleFactory,
        DateFormatRuleFactory $dateFormatRuleFactory,
        EnumRuleFactory $enumRuleFactory,
        IsFloatRuleFactory $isFloatRuleFactory,
        IsIntRuleFactory $isIntRuleFactory,
        IsStringRuleFactory $isStringRuleFactory,
        MinRuleFactory $minRuleFactory,
        NullableRuleFactory $nullableRuleFactory,
        RequiredRuleFactory $requiredRuleFactory,
        UrlRuleFactory $urlRuleFactory,
        string $pattern
    ) {
        $this->dateFormatAtomRuleFactory = $dateFormatAtomRuleFactory;
        $this->dateFormatRuleFactory = $dateFormatRuleFactory;
        $this->enumRuleFactory = $enumRuleFactory;
        $this->isFloatRuleFactory = $isFloatRuleFactory;
        $this->isIntRuleFactory = $isIntRuleFactory;
        $this->isStringRuleFactory = $isStringRuleFactory;
        $this->minRuleFactory = $minRuleFactory;
        $this->nullableRuleFactory = $nullableRuleFactory;
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
        $ruleName = $key;
        $ruleParams = null;

        if (strpos($key, ':') !== false) {
            list($ruleName, $ruleParams) = explode(':', $key, 2);
        }

        $ruleFactoryKey = "{$ruleName}RuleFactory";

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

        $rule = $this->{$ruleFactoryKey}->create();

        if ($ruleParams !== null && $rule instanceof ValidatorRuleWithParamsInterface) {
            $parameters = explode(',', $ruleParams);
            $rule->setParameters($parameters);
        }

        return $rule;
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
