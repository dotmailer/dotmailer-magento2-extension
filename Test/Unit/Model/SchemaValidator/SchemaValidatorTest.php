<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\SchemaValidator;

use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\DateFormatAtomRule;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\DateFormatRule;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\DateFormatAtomRuleFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\DateFormatRuleFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\EnumRule;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\EnumRuleFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\IsFloatRule;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\IsFloatRuleFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\IsIntRule;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\IsIntRuleFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\IsStringRule;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\IsStringRuleFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\MinRule;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\MinRuleFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\NullableRule;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\NullableRuleFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\RequiredRule;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\RequiredRuleFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\UrlRule;
use Dotdigitalgroup\Email\Model\Validator\Schema\Rule\UrlRuleFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\SchemaValidator;
use Dotdigitalgroup\Email\Model\Validator\Schema\SchemaValidatorRule;
use Dotdigitalgroup\Email\Model\Validator\Schema\SchemaValidatorRuleFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\SchemaValidatorRuleSet;
use Dotdigitalgroup\Email\Model\Validator\Schema\SchemaValidatorRuleSetFactory;
use Magento\Framework\Url\Validator;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class SchemaValidatorTest extends TestCase
{
    public const PATTERN = [
        'orderTotal' => ':isFloat',
        'currency' => ':isString',
        'purchaseDate' => ':dateFormat',
        'orderSubtotal' => ':isFloat',
        'products' =>  [
            '*' => [
                'name' => ':isString',
                'price' => ':isFloat',
                'sku' => ':isString',
                'qty' => ':isInt',
                'imagePath' => ':url',
            ]
        ]
    ];

    /**
     * @var SchemaValidatorRuleSetFactory
     */
    private $schemaOrderValidatorRuleSetFactory;

    /**
     * @var SchemaValidator
     */
    private $schemaOrderValidator;

    /**
     * @var Validator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $urlValidatorMock;

    /**
     * Prepare test for run
     *
     * @return void
     * @throws \Dotdigitalgroup\Email\Model\Validator\Schema\Exception\PatternInvalidException
     * @throws \Dotdigitalgroup\Email\Model\Validator\Schema\Exception\RuleNotDefinedException
     */
    protected function setUp() :void
    {
        $this->urlValidatorMock = $this->createMock(Validator::class);
        $this->schemaOrderValidatorRuleSetFactory = $this->createMock(SchemaValidatorRuleSetFactory::class);

        $this->setUpValidator();

        $this->schemaOrderValidator = new SchemaValidator(
            $this->schemaOrderValidatorRuleSetFactory,
            static::PATTERN
        );
    }

    /**
     * Test Valid Order
     *
     * @throws \Dotdigitalgroup\Email\Model\Validator\Schema\Exception\RuleNotDefinedException
     * @throws \Dotdigitalgroup\Email\Model\Validator\Schema\Exception\PatternInvalidException
     */
    public function testPatternMatchIsValid()
    {
        $this->urlValidatorMock->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->schemaOrderValidator->isValid([
            'orderTotal' => 2.12,
            'currency' => 'USD',
            'purchaseDate' => '2022-05-20 14:43:11',
            'orderSubtotal' => 3.66,
            'products' => [
                [
                    'name' => 'mock_product_name',
                    'price' => 0.45,
                    'sku' => 'mock_product_name',
                    'qty' => (int) 5,
                    'imagePath' => 'http://chaz.net/cdn/images/product/my-pic.jpg'
                ]
            ]
        ]);
        $this->assertEmpty($this->schemaOrderValidator->getErrors());
    }

    public function testPatternMatchIsNotValid()
    {
        $this->urlValidatorMock->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $this->schemaOrderValidator->isValid([
            'orderTotal' => "2.12",
            'currency' => null,
            'purchaseDate' => '2022-05-20 14:43:11',
            'orderSubtotal' => "3.66",
            'products' => [
                [
                    'name' => 'mock_product_name',
                    'price' => "0.45",
                    'sku' => 'mock_product_name',
                    'qty' => "5",
                    'imagePath' => 'chaz/cdn/images/product/my-pic.jpg'
                ]
            ]
        ]);
        $this->assertNotEmpty($this->schemaOrderValidator->getErrors());
        $this->assertIsArray($this->schemaOrderValidator->getErrors());
        $this->assertArrayHasKey('orderTotal', $this->schemaOrderValidator->getErrors());
        $this->assertArrayHasKey('orderSubtotal', $this->schemaOrderValidator->getErrors());
        $this->assertArrayHasKey('currency', $this->schemaOrderValidator->getErrors());
        $this->assertArrayHasKey('products.*.price', $this->schemaOrderValidator->getErrors());
        $this->assertArrayHasKey('products.*.qty', $this->schemaOrderValidator->getErrors());
        $this->assertArrayHasKey('products.*.imagePath', $this->schemaOrderValidator->getErrors());
    }

    /**
     * A `nullable` field paired with `dateFormat` should:
     * - pass when the value is null or an empty string (rules skipped)
     * - still be validated against dateFormat when a value is present
     *
     * @throws \Dotdigitalgroup\Email\Model\Validator\Schema\Exception\RuleNotDefinedException
     * @throws \Dotdigitalgroup\Email\Model\Validator\Schema\Exception\PatternInvalidException
     */
    public function testNullableSkipsRemainingRulesOnlyWhenValueIsEmpty()
    {
        $pattern = ['expiresAt' => 'nullable|dateFormat:Y-m-d'];

        $buildValidator = function () use ($pattern) {
            $ruleSetFactory = $this->createMock(SchemaValidatorRuleSetFactory::class);
            $ruleFactory = $this->createMock(SchemaValidatorRuleFactory::class);
            $ruleFactory->method('create')->willReturnOnConsecutiveCalls(
                $this->getRuleFactory('nullable'),
                $this->getRuleFactory('dateFormat:Y-m-d')
            );
            $ruleSetFactory->method('create')->willReturn(
                new SchemaValidatorRuleSet($ruleFactory)
            );

            return new SchemaValidator($ruleSetFactory, $pattern);
        };

        $this->assertTrue($buildValidator()->isValid(['expiresAt' => null]));
        $this->assertTrue($buildValidator()->isValid(['expiresAt' => '']));
        $this->assertTrue($buildValidator()->isValid(['expiresAt' => '2024-01-01']));

        $invalidValueValidator = $buildValidator();
        $this->assertFalse($invalidValueValidator->isValid(['expiresAt' => 'not-a-date']));
        $this->assertArrayHasKey('expiresAt', $invalidValueValidator->getErrors());
    }

    /**
     * @throws \Dotdigitalgroup\Email\Model\Validator\Schema\Exception\RuleNotDefinedException
     */
    private function getRuleFactory($rule)
    {
        $dateFormatAtomRuleFactory = $this->createMock(DateFormatAtomRuleFactory::class);
        $dateFormatAtomRuleFactory
            ->method('create')
            ->willReturn(new DateFormatAtomRule());
        $dateFormatRuleFactory = $this->createMock(DateFormatRuleFactory::class);
        $dateFormatRuleFactory
            ->method('create')
            ->willReturn(new DateFormatRule());
        $enumRuleFactory = $this->createMock(EnumRuleFactory::class);
        $enumRuleFactory
            ->method('create')
            ->willReturn(new EnumRule());
        $isFloatRuleFactory = $this->createMock(IsFloatRuleFactory::class);
        $isFloatRuleFactory
            ->method('create')
            ->willReturn(new IsFloatRule());
        $isIntRuleFactory = $this->createMock(IsIntRuleFactory::class);
        $isIntRuleFactory
            ->method('create')
            ->willReturn(new IsIntRule());
        $isStringRuleFactory = $this->createMock(IsStringRuleFactory::class);
        $isStringRuleFactory
            ->method('create')
            ->willReturn(new IsStringRule());
        $minRuleFactory = $this->createMock(MinRuleFactory::class);
        $minRuleFactory
            ->method('create')
            ->willReturn(new MinRule());
        $nullableRuleFactory = $this->createMock(NullableRuleFactory::class);
        $nullableRuleFactory
            ->method('create')
            ->willReturn(new NullableRule());
        $requiredRuleFactory = $this->createMock(RequiredRuleFactory::class);
        $requiredRuleFactory
            ->method('create')
            ->willReturn(new RequiredRule());
        $urlRuleFactory = $this->createMock(UrlRuleFactory::class);
        $urlRuleFactory
            ->method('create')
            ->willReturn(new UrlRule($this->urlValidatorMock));

        return new SchemaValidatorRule(
            $dateFormatAtomRuleFactory,
            $dateFormatRuleFactory,
            $enumRuleFactory,
            $isFloatRuleFactory,
            $isIntRuleFactory,
            $isStringRuleFactory,
            $minRuleFactory,
            $nullableRuleFactory,
            $requiredRuleFactory,
            $urlRuleFactory,
            $rule
        );
    }

    /**
     * Prepare test classes for validation
     *
     * @return void
     * @throws Exception
     */
    private function setUpValidator(): void
    {
        $matcher = $this->exactly(9);
        $schemaOrderValidatorRuleMockFactory = $this->createMock(SchemaValidatorRuleFactory::class);
        $schemaOrderValidatorRuleMockFactory->expects($this->exactly(9))
            ->method('create')
            ->willReturnCallback(function () use ($matcher) {
                return match ($matcher->numberOfInvocations()) {
                    1 => [["pattern" => 'isFloat']],
                    2 => [["pattern" => 'isString']],
                    3 => [["pattern" => 'dateFormat']],
                    4 => [["pattern" => 'isFloat']],
                    5 => [["pattern" => 'isString']],
                    6 => [["pattern" => 'isFloat']],
                    7 => [["pattern" => 'isString']],
                    8 => [["pattern" => 'isInt']],
                    9 => [["pattern" => 'url']]
                };
            })
            ->willReturnOnConsecutiveCalls(
                $this->getRuleFactory('isFloat'),
                $this->getRuleFactory('isString'),
                $this->getRuleFactory('dateFormat'),
                $this->getRuleFactory('isFloat'),
                $this->getRuleFactory('isString'),
                $this->getRuleFactory('isFloat'),
                $this->getRuleFactory('isString'),
                $this->getRuleFactory('isInt'),
                $this->getRuleFactory('url')
            );

        $matcher = $this->exactly(11);
        $this->schemaOrderValidatorRuleSetFactory->expects($this->exactly(11))
            ->method('create')
            ->willReturnCallback(function () use ($matcher) {
                return match ($matcher->numberOfInvocations()) {
                    1 => [["key" => 'orderTotal']],
                    2 => [["key" => 'currency']],
                    3 => [["key" => 'purchaseDate']],
                    4 => [["key" => 'orderSubtotal']],
                    5 => [["key" => 'products']],
                    6 => [["key" => 'products.*']],
                    7 => [["key" => 'products.*.name']],
                    8 => [["key" => 'products.*.price']],
                    9 => [["key" => 'products.*.sku']],
                    10 => [["key" => 'products.*.qty']],
                    11 => [["key" => 'products.*.imagePath']]
                };
            })
            ->willReturn(
                new SchemaValidatorRuleSet($schemaOrderValidatorRuleMockFactory),
                new SchemaValidatorRuleSet($schemaOrderValidatorRuleMockFactory),
                new SchemaValidatorRuleSet($schemaOrderValidatorRuleMockFactory),
                new SchemaValidatorRuleSet($schemaOrderValidatorRuleMockFactory),
                new SchemaValidatorRuleSet($schemaOrderValidatorRuleMockFactory),
                new SchemaValidatorRuleSet($schemaOrderValidatorRuleMockFactory),
                new SchemaValidatorRuleSet($schemaOrderValidatorRuleMockFactory),
                new SchemaValidatorRuleSet($schemaOrderValidatorRuleMockFactory),
                new SchemaValidatorRuleSet($schemaOrderValidatorRuleMockFactory),
                new SchemaValidatorRuleSet($schemaOrderValidatorRuleMockFactory),
                new SchemaValidatorRuleSet($schemaOrderValidatorRuleMockFactory),
            );
    }
}
