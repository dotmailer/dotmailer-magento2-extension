<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\SalesRule;

use Dotdigitalgroup\Email\Model\SalesRule\DotdigitalCouponCodeGenerator;
use Magento\SalesRule\Helper\Coupon;
use PHPUnit\Framework\TestCase;

class DotdigitalCouponCodeGeneratorTest extends TestCase
{
    /**
     * @var Coupon|\PHPUnit_Framework_MockObject_MockObject
     */
    private $couponHelperMock;

    /**
     * @var DotdigitalCouponCodeGenerator
     */
    private $model;

    /**
     * Prepare data
     */
    protected function setUp() :void
    {
        $this->couponHelperMock = $this->getMockBuilder(Coupon::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->couponHelperMock->expects($this->any())
            ->method('getFormatsList')
            ->willReturn([
                Coupon::COUPON_FORMAT_ALPHANUMERIC => __('Alphanumeric'),
                Coupon::COUPON_FORMAT_ALPHABETICAL => __('Alphabetical'),
                Coupon::COUPON_FORMAT_NUMERIC => __('Numeric')
            ]);

        $this->model = new DotdigitalCouponCodeGenerator($this->couponHelperMock);
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
