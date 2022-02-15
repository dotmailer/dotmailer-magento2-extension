<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\AbandonedCart\ProgramEnrolment;

use Dotdigitalgroup\Email\Model\DateTimeFactory;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment\Interval;
use PHPUnit\Framework\TestCase;

class ProgramEnrolmentIntervalTest extends TestCase
{
    /**
     * @var DateTimeFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dateTimeFactoryMock;

    /**
     * @var Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dataHelperMock;

    /**
     * @var Interval
     */
    private $model;

    protected function setUp() :void
    {
        $this->dateTimeFactoryMock = $this->getMockBuilder(DateTimeFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new Interval(
            $this->dateTimeFactoryMock,
            $this->dataHelperMock
        );
    }

    public function testTimeWindowWasSet()
    {
        $storeId = 1;
        $minutes = 30;

        // DateTime
        $dateTimeMock = $this->createMock(\DateTime::class);
        $this->dateTimeFactoryMock->expects($this->once())
            ->method('create')
            ->with([
                'time' => 'now',
                'timezone' => new \DateTimezone('UTC')
            ])
            ->willReturn($dateTimeMock);

        // Scope config
        $scopeConfigModelMock = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);

        $this->dataHelperMock->expects($this->once())
            ->method('getScopeConfig')
            ->willReturn($scopeConfigModelMock);

        $scopeConfigModelMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_LOSTBASKET_ENROL_TO_PROGRAM_INTERVAL,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            )
            ->willReturn($minutes);


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
