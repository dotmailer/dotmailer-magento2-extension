<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\SalesRule;

use PHPUnit\Framework\TestCase;
use Dotdigitalgroup\Email\Model\SalesRule\DotdigitalCouponRequestProcessor;
use Magento\SalesRule\Model\Rule as RuleModel;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class DotdigitalCouponRequestProcessorTest extends TestCase
{
    /**
     * @var DotdigitalCouponRequestProcessor
     */
    private $_dotdigitalCouponRequestProcessor;

    /**
     * @var TimezoneInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $_localeDateMock;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->_localeDateMock = $this->createMock(TimezoneInterface::class);
        $this->_dotdigitalCouponRequestProcessor = new DotdigitalCouponRequestProcessor(
            $this->createMock(\Dotdigitalgroup\Email\Logger\Logger::class),
            $this->createMock(\Magento\SalesRule\Model\RuleFactory::class),
            $this->createMock(\Magento\SalesRule\Model\ResourceModel\Rule::class),
            $this->createMock(\Magento\Framework\App\RequestInterface::class),
            $this->createMock(\Magento\Framework\Stdlib\DateTime\DateTime::class),
            $this->_localeDateMock,
            $this->createMock(\Dotdigitalgroup\Email\Model\Coupon\CouponAttributeCollectionFactory::class),
            $this->createMock(\Dotdigitalgroup\Email\Model\SalesRule\DotdigitalCouponGenerator::class)
        );
    }

    /**
     * Test that the rule is not expired.
     *
     * @return void
     */
    public function testIsRuleNotExpired()
    {
        $ruleMock = $this->createMock(RuleModel::class);
        $ruleMock->method('getToDate')->willReturn('2025-03-05');

        $this->_localeDateMock->method('getConfigTimezone')
            ->willReturn('UTC');
        $this->_localeDateMock->method('date')
            ->willReturn(new \DateTime('2025-03-05 00:00:00', new \DateTimeZone('UTC')));

        $this->assertFalse($this->_invokeIsRuleExpired($ruleMock));
    }

    /**
     * Test that the rule is expired.
     *
     * @return void
     */
    public function testIsRuleExpired()
    {
        $ruleMock = $this->createMock(RuleModel::class);
        $ruleMock->method('getToDate')->willReturn('2025-03-05');

        $this->_localeDateMock->method('getConfigTimezone')
            ->willReturn('UTC');
        $this->_localeDateMock->method('date')
            ->willReturn(new \DateTime('2025-03-06 00:00:00', new \DateTimeZone('UTC')));

        $this->assertTrue($this->_invokeIsRuleExpired($ruleMock));
    }

    /**
     * Invoke the isRuleExpired method on DotdigitalCouponRequestProcessor.
     *
     * @param  RuleModel $rule
     * @return bool
     */
    private function _invokeIsRuleExpired($rule)
    {
        $reflection = new \ReflectionClass($this->_dotdigitalCouponRequestProcessor);
        $method = $reflection->getMethod('isRuleExpired');
        $method->setAccessible(true);

        return $method->invokeArgs($this->_dotdigitalCouponRequestProcessor, [$rule]);
    }
}
