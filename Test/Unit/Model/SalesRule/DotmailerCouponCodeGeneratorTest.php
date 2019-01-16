<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\SalesRule;

use Dotdigitalgroup\Email\Model\SalesRule\DotmailerCouponCodeGenerator;
use Magento\SalesRule\Helper\Coupon;
use PHPUnit\Framework\TestCase;

class DotmailerCouponCodeGeneratorTest extends TestCase
{
    /**
     * @var Coupon|\PHPUnit_Framework_MockObject_MockObject
     */
    private $couponHelperMock;

    /**
     * @var DotmailerCouponCodeGenerator
     */
    private $model;

    /**
     * Prepare data
     */
    protected function setUp()
    {
        $this->couponHelperMock = $this->getMockBuilder(Coupon::class)
                                       ->disableOriginalConstructor()
                                       ->getMock();

        $this->model = new DotmailerCouponCodeGenerator($this->couponHelperMock);
    }

    public function testCouponCodeDelimiterRetrievedFromCouponHelper()
    {
        $expectedSeparator = "^";
        $this->couponHelperMock->expects($this->once())
                               ->method('getCodeSeparator')
                               ->willReturn($expectedSeparator);

        $actualSeparator = $this->model->getDelimiter();

        $this->assertEquals($expectedSeparator, $actualSeparator);
    }

    public function testCouponFormatCorrect()
    {
        $delimiter = "$";
        $this->couponHelperMock->method('getCodeSeparator')
                               ->willReturn($delimiter);
        $this->couponHelperMock->method('getCharset')
                               ->willReturn(['1','2','3','A','S','D']);

        $couponCode = $this->model->generateCode();

        $this->assertStringMatchesFormat('DOT-%c%c%c$%c%c%c$%c%c%c', $couponCode);
    }
}
