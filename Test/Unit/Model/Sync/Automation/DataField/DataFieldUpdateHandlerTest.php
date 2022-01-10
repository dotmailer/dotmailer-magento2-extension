<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Automation\DataField;

use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldUpdateHandler;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldUpdater;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\Updater\Order;
use PHPUnit\Framework\TestCase;

class DataFieldUpdateHandlerTest extends TestCase
{
    /**
     * @var DataFieldUpdateHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dataFieldUpdateHandler;

    /**
     * @var DataFieldUpdater|\PHPUnit_Framework_MockObject_MockObject
     */
    private $defaultUpdaterMock;

    /**
     * @var Order|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderUpdaterMock;

    protected function setUp() :void
    {
        $this->defaultUpdaterMock = $this->createMock(DataFieldUpdater::class);
        $this->orderUpdaterMock = $this->createMock(Order::class);

        $this->dataFieldUpdateHandler = new DataFieldUpdateHandler(
            $this->defaultUpdaterMock,
            $this->orderUpdaterMock
        );
    }

    public function testUpdateDataFieldsViaDefaultUpdater()
    {
        $defaultTypeAutomations = $this->setupDefaultTypeAutomationData();

        $this->defaultUpdaterMock->expects($this->exactly(5))
            ->method('setDefaultDataFields')
            ->willReturn($this->defaultUpdaterMock);

        $this->defaultUpdaterMock->expects($this->exactly(5))
            ->method('updateDataFields');

        $this->orderUpdaterMock->expects($this->never())
            ->method('setDataFields');

        foreach ($defaultTypeAutomations as $automation) {
            $this->dataFieldUpdateHandler->updateDataFieldsByType(
                $automation['type'],
                $automation['email'],
                $automation['websiteId'],
                $automation['typeId'],
                $automation['storeName']
            );
        }
    }

    public function testUpdateDataFieldsViaOrderUpdater()
    {
        $orderTypeAutomations = $this->setupOrderTypeAutomationData();

        $this->orderUpdaterMock->expects($this->exactly(4))
            ->method('setDataFields')
            ->willReturn($this->orderUpdaterMock);

        $this->orderUpdaterMock->expects($this->exactly(4))
            ->method('updateDataFields');

        $this->defaultUpdaterMock->expects($this->never())
            ->method('setDefaultDataFields');

        foreach ($orderTypeAutomations as $automation) {
            $this->dataFieldUpdateHandler->updateDataFieldsByType(
                $automation['type'],
                $automation['email'],
                $automation['websiteId'],
                $automation['typeId'],
                $automation['storeName']
            );
        }
    }

    /**
     * @return array[]
     */
    private function setupOrderTypeAutomationData()
    {
        return [
            [
                'type' => AutomationTypeHandler::AUTOMATION_TYPE_NEW_ORDER,
                'email' => 'chaz@kangaroo.com',
                'websiteId' => 1,
                'typeId' => 20,
                'storeName' => 'Default Store View'
            ],
            [
                'type' => AutomationTypeHandler::AUTOMATION_TYPE_NEW_GUEST_ORDER,
                'email' => 'chaz@kangaroo.com',
                'websiteId' => 1,
                'typeId' => 20,
                'storeName' => 'Default Store View'
            ],
            [
                'type' => AutomationTypeHandler::AUTOMATION_TYPE_CUSTOMER_FIRST_ORDER,
                'email' => 'chaz@kangaroo.com',
                'websiteId' => 1,
                'typeId' => 20,
                'storeName' => 'Default Store View'
            ],
            [
                'type' => AutomationTypeHandler::ORDER_STATUS_AUTOMATION,
                'email' => 'chaz@kangaroo.com',
                'websiteId' => 1,
                'typeId' => 20,
                'storeName' => 'Default Store View'
            ]
        ];
    }

    /**
     * @return array[]
     */
    private function setupDefaultTypeAutomationData()
    {
        return [
            [
                'type' => AutomationTypeHandler::AUTOMATION_TYPE_NEW_CUSTOMER,
                'email' => 'chaz@kangaroo.com',
                'websiteId' => 1,
                'typeId' => 20,
                'storeName' => 'Default Store View'
            ],
            [
                'type' => AutomationTypeHandler::AUTOMATION_TYPE_NEW_SUBSCRIBER,
                'email' => 'chaz@kangaroo.com',
                'websiteId' => 1,
                'typeId' => 20,
                'storeName' => 'Default Store View'
            ],
            [
                'type' => AutomationTypeHandler::AUTOMATION_TYPE_NEW_REVIEW,
                'email' => 'chaz@kangaroo.com',
                'websiteId' => 1,
                'typeId' => 20,
                'storeName' => 'Default Store View'
            ],
            [
                'type' => AutomationTypeHandler::AUTOMATION_TYPE_NEW_WISHLIST,
                'email' => 'chaz@kangaroo.com',
                'websiteId' => 1,
                'typeId' => 20,
                'storeName' => 'Default Store View'
            ],
            [
                'type' => AutomationTypeHandler::AUTOMATION_TYPE_ABANDONED_CART_PROGRAM_ENROLMENT,
                'email' => 'chaz@kangaroo.com',
                'websiteId' => 1,
                'typeId' => 20,
                'storeName' => 'Default Store View'
            ]
        ];
    }
}
