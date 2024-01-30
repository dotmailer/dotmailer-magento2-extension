<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Cron;

use Dotdigitalgroup\Email\Model\Cron\CronFromTimeSetter;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterfaceFactory;
use PHPUnit\Framework\TestCase;

class CronFromTimeSetterTest extends TestCase
{
    /**
     * @var DateTimeFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dateTimeFactoryMock;

    /**
     * @var TimezoneInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $timezoneInterfaceFactoryMock;

    /**
     * @var CronFromTimeSetter
     */
    private $model;

    /**
     * Set up
     */
    protected function setUp() :void
    {
        $this->dateTimeFactoryMock = $this->createMock(DateTimeFactory::class);
        $this->timezoneInterfaceFactoryMock = $this->createMock(TimezoneInterfaceFactory::class);

        $this->model = new CronFromTimeSetter(
            $this->dateTimeFactoryMock,
            $this->timezoneInterfaceFactoryMock
        );
    }

    public function testCronFromTimeIsSetFromSuppliedTime()
    {
        $magentoDateTimeMock = $this->createMock(\Magento\Framework\Stdlib\DateTime\DateTime::class);

        $this->dateTimeFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($magentoDateTimeMock);

        $this->timezoneInterfaceFactoryMock->expects($this->never())
            ->method('create');

        $this->model->setFromTime('2023-05-22 16:45:10');
    }

    public function testCronFromTimeUsesFallbackIfNoSuppliedTime()
    {
        $dateTimeMock = $this->createMock(\DateTime::class);
        $magentoDateTimeMock = $this->createMock(\Magento\Framework\Stdlib\DateTime\DateTime::class);
        $timezoneInterfaceMock = $this->createMock(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class);

        $this->timezoneInterfaceFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($timezoneInterfaceMock);

        $timezoneInterfaceMock->expects($this->once())
            ->method('date')
            ->willReturn($dateTimeMock);

        $dateTimeMock->expects($this->once())
            ->method('sub');

        $this->dateTimeFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($magentoDateTimeMock);

        $magentoDateTimeMock->expects($this->once())
            ->method('date');

        $this->model->setFromTime();
    }
}
