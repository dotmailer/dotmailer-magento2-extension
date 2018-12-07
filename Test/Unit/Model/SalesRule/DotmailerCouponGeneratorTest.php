<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\SalesRule;

use Dotdigitalgroup\Email\Model\SalesRule\DotmailerCouponGenerator;
use Magento\Framework\Stdlib\DateTime;
use Magento\SalesRule\Model\Coupon\CodegeneratorInterface;
use Magento\SalesRule\Model\ResourceModel\Rule;
use Magento\SalesRule\Model\RuleFactory;
use Magento\SalesRule\Model\Spi\CouponResourceInterface;
use PHPUnit\Framework\TestCase;

class DotmailerCouponGeneratorTest extends TestCase
{
    /**
     * @var RuleFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $ruleModelFactoryMock;

    /**
     * @var Rule|\PHPUnit_Framework_MockObject_MockObject
     */
    private $ruleResourceMock;

    /**
     * @var CodegeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $codeGeneratorMock;

    /**
     * @var CouponResourceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $couponResourceMock;

    /**
     * @var DateTime|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dateTimeMock;

    /**
     * @var DotmailerCouponGenerator
     */
    private $model;

    /**
     * Prepare data
     */
    protected function setUp()
    {
        $this->ruleModelFactoryMock = $this->getMockBuilder(RuleFactory::class)
                                           ->disableOriginalConstructor()
                                           ->getMock();
        $this->ruleResourceMock     = $this->getMockBuilder(Rule::class)
                                           ->disableOriginalConstructor()
                                           ->getMock();
        $this->codeGeneratorMock    = $this->getMockBuilder(CodegeneratorInterface::class)
                                           ->disableOriginalConstructor()
                                           ->getMock();
        $this->couponResourceMock   = $this->getMockBuilder(CouponResourceInterface::class)
                                           ->disableOriginalConstructor()
                                           ->getMock();
        $this->dateTimeMock         = $this->getMockBuilder(DateTime::class)
                                           ->disableOriginalConstructor()
                                           ->getMock();
        $this->model                = new DotmailerCouponGenerator(
            $this->ruleModelFactoryMock,
            $this->ruleResourceMock,
            $this->codeGeneratorMock,
            $this->couponResourceMock,
            $this->dateTimeMock
        );
    }

    public function testCouponIsCreated()
    {
        $priceRuleId        = 2134;
        $expectedCouponCode = "TEST-COUPON-CODE";
        $rule               = $this->setUpForRuleCreation($priceRuleId);
        $coupon             = $this->getMockBuilder(\Magento\SalesRule\Model\Coupon::class)
                                   ->disableOriginalConstructor()
                                   ->getMock();
        $this->setUpRuleToReturnCoupon($rule, $coupon);

        $this->couponResourceMock->expects($this->once())
                                 ->method('save')
                                 ->with($coupon);

        $coupon->expects($this->once())
               ->method('getCode')
               ->willReturn($expectedCouponCode);

        $actualCouponCode = $this->model->generateCoupon($priceRuleId, null);

        $this->assertEquals($expectedCouponCode, $actualCouponCode);
    }

    public function testRuleExpiryDateIsUsedIfSetAndNoDateProvided()
    {
        $priceRuleId        = 2134;
        $expectedCouponCode = "TEST-COUPON-CODE";
        $rule               = $this->setUpForRuleCreation($priceRuleId);
        $coupon             = $this->getMockBuilder(\Magento\SalesRule\Model\Coupon::class)
                                   ->disableOriginalConstructor()
                                   ->getMock();
        $this->setUpRuleToReturnCoupon($rule, $coupon);

        $this->couponResourceMock->expects($this->once())
                                 ->method('save')
                                 ->with($coupon);

        $coupon->expects($this->once())
               ->method('getCode')
               ->willReturn($expectedCouponCode);

        $date = \DateTime::createFromFormat('j-M-Y', '15-Feb-2018');

        $rule->method('getToDate')
             ->willReturn($date);
        $coupon->expects($this->once())
               ->method('setExpirationDate')
               ->with($date);

        $this->model->generateCoupon($priceRuleId, null);
    }

    public function testNoExpiryIfRuleHasNoneAndNoDateProvided()
    {
        $priceRuleId        = 2134;
        $expectedCouponCode = "TEST-COUPON-CODE";
        $rule               = $this->setUpForRuleCreation($priceRuleId);
        $coupon             = $this->getMockBuilder(\Magento\SalesRule\Model\Coupon::class)
                                   ->disableOriginalConstructor()
                                   ->getMock();
        $this->setUpRuleToReturnCoupon($rule, $coupon);

        $this->couponResourceMock->expects($this->once())
                                 ->method('save')
                                 ->with($coupon);

        $coupon->expects($this->once())
               ->method('getCode')
               ->willReturn($expectedCouponCode);


        $rule->method('getToDate')
             ->willReturn(null);
        $coupon->expects($this->once())
               ->method('setExpirationDate')
               ->with(null);

        $this->model->generateCoupon($priceRuleId, null);
    }

    public function testInputtedExpirySetWhenRuleHasNone()
    {
        $priceRuleId        = 2134;
        $expectedCouponCode = "TEST-COUPON-CODE";
        $rule               = $this->setUpForRuleCreation($priceRuleId);
        $coupon             = $this->getMockBuilder(\Magento\SalesRule\Model\Coupon::class)
                                   ->disableOriginalConstructor()
                                   ->getMock();
        $this->setUpRuleToReturnCoupon($rule, $coupon);

        $this->couponResourceMock->expects($this->once())
                                 ->method('save')
                                 ->with($coupon);

        $coupon->expects($this->once())
               ->method('getCode')
               ->willReturn($expectedCouponCode);

        $date = \DateTime::createFromFormat('j-M-Y', '15-Feb-2018');

        $rule->method('getToDate')
             ->willReturn(null);
        $coupon->expects($this->once())
               ->method('setExpirationDate')
               ->with($date);

        $this->model->generateCoupon($priceRuleId, $date);
    }

    public function testInputtedExpirySetWhenRuleHasExpiry()
    {
        $priceRuleId        = 2134;
        $expectedCouponCode = "TEST-COUPON-CODE";
        $rule               = $this->setUpForRuleCreation($priceRuleId);
        $coupon             = $this->getMockBuilder(\Magento\SalesRule\Model\Coupon::class)
                                   ->disableOriginalConstructor()
                                   ->getMock();
        $this->setUpRuleToReturnCoupon($rule, $coupon);

        $this->couponResourceMock->expects($this->once())
                                 ->method('save')
                                 ->with($coupon);

        $coupon->expects($this->once())
               ->method('getCode')
               ->willReturn($expectedCouponCode);

        $date = \DateTime::createFromFormat('j-M-Y', '15-Feb-2018');

        $rule->method('getToDate')
             ->willReturn(\DateTime::createFromFormat('j-M-Y', '15-Feb-2019'));
        $coupon->expects($this->once())
               ->method('setExpirationDate')
               ->with($date);

        $this->model->generateCoupon($priceRuleId, $date);
    }

    /**
     * @param $priceRuleId
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function setUpForRuleCreation($priceRuleId)
    {
        $rule = $this->getMockBuilder(\Magento\SalesRule\Model\Rule::class)
                     ->disableOriginalConstructor()
                     ->getMock();
        $this->ruleModelFactoryMock->expects($this->once())
                                   ->method('create')
                                   ->willReturn($rule);
        $rule->expects($this->once())
             ->method('__call')
             ->with(
                 $this->equalTo('setCouponType'),
                 $this->equalTo([\Magento\SalesRule\Model\Rule::COUPON_TYPE_AUTO])
             );
        $this->ruleResourceMock->expects($this->once())
                               ->method('load')
                               ->with($rule, $priceRuleId)
                               ->willReturn($rule);

        return $rule;
    }

    /**
     * @param $rule
     * @param $coupon
     */
    private function setUpRuleToReturnCoupon($rule, $coupon)
    {
        $rule->expects($this->once())
            ->method('setCouponCodeGenerator')
            ->with($this->codeGeneratorMock);

        $rule->expects($this->once())
             ->method('acquireCoupon')
             ->willReturn($coupon);

        $dateTime = new \DateTime();
        $this->dateTimeMock->expects($this->once())
                           ->method('formatDate')
                           ->with(true)
                           ->willReturn($dateTime);
        $coupon->expects($this->once())
               ->method('setCreatedAt')
               ->with($dateTime);

        $coupon->expects($this->once())
               ->method('__call')
               ->with(
                   $this->equalTo('setGeneratedByDotmailer'),
                   $this->equalTo([1])
               );

        $coupon->expects($this->once())
               ->method('setType')
               ->with(\Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON);
    }
}
