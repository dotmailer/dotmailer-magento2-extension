<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Automation\DataField;

use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldTypeHandler;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldUpdater;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\Updater\Order;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldUpdaterFactory;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\Updater\OrderFactory;
use PHPUnit\Framework\TestCase;

class DataFieldTypeHandlerTest extends TestCase
{
    /**
     * @var DataFieldTypeHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dataFieldTypeHandler;

    /**
     * @var DataFieldUpdater|\PHPUnit_Framework_MockObject_MockObject
     */
    private $defaultUpdaterMock;

    /**
     * @var Order|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderUpdaterMock;

    /**
     * @var DataFieldUpdaterFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $defaultUpdaterFactoryMock;

    /**
     * @var OrderFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderUpdaterFactoryMock;

    protected function setUp() :void
    {
        $this->defaultUpdaterMock = $this->createMock(DataFieldUpdater::class);
        $this->orderUpdaterMock = $this->createMock(Order::class);
        $this->defaultUpdaterFactoryMock = $this->createMock(DataFieldUpdaterFactory::class);
        $this->orderUpdaterFactoryMock = $this->createMock(OrderFactory::class);

        $this->dataFieldTypeHandler = new DataFieldTypeHandler(
            $this->defaultUpdaterFactoryMock,
            $this->orderUpdaterFactoryMock
        );
    }

    public function testUpdateDataFieldsViaDefaultUpdater()
    {
        $defaultTypeAutomations = $this->setupDefaultTypeAutomationData();

        $this->defaultUpdaterFactoryMock->expects($this->exactly(5))
            ->method('create')
            ->willReturn($this->defaultUpdaterMock);

        $this->defaultUpdaterMock->expects($this->exactly(5))
            ->method('setDefaultDataFields')
            ->willReturn($this->defaultUpdaterMock);

        $this->defaultUpdaterMock->expects($this->exactly(5))
            ->method('getData')
            ->willReturn($this->getDefaultDataFields());

        $this->orderUpdaterMock->expects($this->never())
            ->method('setDataFields');

        foreach ($defaultTypeAutomations as $automation) {
            $this->dataFieldTypeHandler->retrieveDatafieldsByType(
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

        $this->orderUpdaterFactoryMock->expects($this->exactly(4))
            ->method('create')
            ->willReturn($this->orderUpdaterMock);

        $this->orderUpdaterMock->expects($this->exactly(4))
            ->method('setDataFields')
            ->willReturn($this->orderUpdaterMock);

        $this->orderUpdaterMock->expects($this->exactly(4))
            ->method('getData')
            ->willReturn([
                ...$this->getDefaultDataFields(),
                ...$this->getOrderDataFields()
            ]);

        $this->defaultUpdaterMock->expects($this->never())
            ->method('setDefaultDataFields');

        foreach ($orderTypeAutomations as $automation) {
            $this->dataFieldTypeHandler->retrieveDatafieldsByType(
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

    private function getDefaultDataFields()
    {
        return [
            [
                'Key' => 'STORE_NAME',
                'Value' => 'Chaz store',
            ],
            [
                'Key' => 'WEBSITE_NAME',
                'Value' => 'Chaz website',
            ]
        ];
    }

    private function getOrderDataFields()
    {
        return [
            [
                'Key' => 'LAST_ORDER_ID',
                'Value' => '101',
            ],
            [
                'Key' => 'LAST_INCREMENT_ID',
                'Value' => '100000101',
            ]
        ];
    }
}
