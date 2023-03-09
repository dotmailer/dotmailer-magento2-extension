<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\AbandonedCart\ProgramEnrolment;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\AbandonedCart\Interval;
use Dotdigitalgroup\Email\Model\Sync\SyncTimeService;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;

class ProgramEnrolmentIntervalTest extends TestCase
{
    /**
     * @var SyncTimeService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $syncTimeServiceMock;

    /**
     * @var ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfigMock;

    /**
     * @var Interval
     */
    private $model;

    protected function setUp() :void
    {
        $this->syncTimeServiceMock = $this->getMockBuilder(SyncTimeService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new Interval(
            $this->syncTimeServiceMock,
            $this->scopeConfigMock
        );
    }

    public function testTimeWindowWasSetForEnrolment()
    {
        $storeId = 1;
        $minutes = 30;

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Config::XML_PATH_LOSTBASKET_ENROL_TO_PROGRAM_INTERVAL,
                ScopeInterface::SCOPE_STORE,
                $storeId
            )
            ->willReturn($minutes);

        $dateTimeMock = $this->createMock(\DateTime::class);

        $this->syncTimeServiceMock->expects($this->once())
            ->method('getSyncFromTime')
            ->willReturn(null);

        $this->syncTimeServiceMock->expects($this->once())
            ->method('getSyncToTime')
            ->willReturn($dateTimeMock);

        $dateTimeMock->expects($this->atLeastOnce())
            ->method('sub')
            ->willReturn($dateTimeMock);

        $fromString = "2018-01-01 10:00:00";
        $toString = "2018-01-02 10:00:00";

        $dateTimeMock->expects($this->exactly(2))
            ->method('format')
            ->withConsecutive(
                [$this->equalTo('Y-m-d H:i:s')],
                [$this->equalTo('Y-m-d H:i:s')]
            )
            ->willReturnOnConsecutiveCalls(
                $fromString,
                $toString
            );

        $timeWindow = $this->model->getAbandonedCartProgramEnrolmentWindow($storeId);

        $this->assertSame($timeWindow['from'], $fromString);
        $this->assertSame($timeWindow['to'], $toString);
    }
}
