<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Automation\DataField;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldUpdater;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\TestCase;

class DataFieldUpdaterTest extends TestCase
{
    /**
     * @var DataFieldUpdater
     */
    private $dataFieldUpdater;

    /**
     * @var Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $helperMock;

    /**
     * @var Client|\PHPUnit\Framework\MockObject\MockObject
     */
    private $clientMock;

    /**
     * @var StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManagerMock;

    /**
     * @var Website|\PHPUnit_Framework_MockObject_MockObject
     */
    private $websiteMock;

    /**
     * @var int
     */
    private $websiteId;

    protected function setUp() :void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->websiteMock = $this->createMock(Website::class);
        $this->clientMock = $this->createMock(Client::class);

        $this->dataFieldUpdater = new DataFieldUpdater(
            $this->helperMock,
            $this->storeManagerMock
        );

        $this->websiteMock->method('getId')
            ->willReturn($this->websiteId = 1234);
    }

    public function testDefaultDataFieldsAreSet()
    {
        $email = 'chaz@kangaroo.com';
        $websiteId = 1;
        $storeName = 'Default Store View';
        $websiteName = 'Main Website';

        $this->setDataFields();

        $this->dataFieldUpdater->setDefaultDataFields(
            $email,
            $websiteId,
            $storeName
        );

        $this->assertEquals(
            $this->getExpectedData($storeName, $websiteName),
            $this->dataFieldUpdater->getData()
        );
    }

    public function testUpdateDataFields()
    {
        $email = 'chaz@kangaroo.com';
        $websiteId = 1;
        $storeName = 'Default Store View';

        $this->setDataFields();

        $this->dataFieldUpdater->setDefaultDataFields(
            $email,
            $websiteId,
            $storeName
        );

        $this->helperMock->expects($this->once())
            ->method('getWebsiteApiClient')
            ->with($this->websiteId)
            ->willReturn($this->clientMock);

        $this->dataFieldUpdater->updateDataFields();
    }

    public function testDataFieldsAreNotUpdatedIfEmpty()
    {
        $email = 'chaz@kangaroo.com';
        $websiteId = 1;
        $storeName = 'Default Store View';
        $websiteName = 'Main Website';

        // setting data fields if none are mapped
        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($this->websiteMock);

        $this->websiteMock->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturnOnConsecutiveCalls(null, null);

        $this->websiteMock->expects($this->never())
            ->method('getName')
            ->willReturn($websiteName);

        $this->dataFieldUpdater->setDefaultDataFields(
            $email,
            $websiteId,
            $storeName
        );

        $this->helperMock->expects($this->never())
            ->method('getWebsiteApiClient');

        $this->dataFieldUpdater->updateDataFields();
    }

    private function setDataFields()
    {
        $websiteName = 'Main Website';

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($this->websiteMock);

        $this->websiteMock->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturnOnConsecutiveCalls('STORE_NAME', 'WEBSITE_NAME');

        $this->websiteMock->expects($this->once())
            ->method('getName')
            ->willReturn($websiteName);
    }

    /**
     * @param $storeName
     * @param $websiteName
     * @return array[]
     */
    private function getExpectedData($storeName, $websiteName)
    {
        return [
            [
                'Key' => 'STORE_NAME',
                'Value' => $storeName
            ],
            [
                'Key' => 'WEBSITE_NAME',
                'Value' => $websiteName
            ]
        ];
    }
}
