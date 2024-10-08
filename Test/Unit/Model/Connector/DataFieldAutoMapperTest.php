<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Connector;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Connector\DataFieldAutoMapper;
use Dotdigitalgroup\Email\Model\Connector\Datafield;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataFieldAutoMapperTest extends TestCase
{
    /**
     * @var MockObject|Data
     */
    private $helperMock;

    /**
     * @var MockObject|Datafield
     */
    private $dataFieldMock;

    /**
     * @var MockObject|ReinitableConfigInterface
     */
    private $reinitableConfigMock;

    /**
     * @var DataFieldAutoMapper
     */
    private $dataFieldAutoMapper;

    protected function setUp(): void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->dataFieldMock = $this->createMock(Datafield::class);
        $this->reinitableConfigMock = $this->createMock(ReinitableConfigInterface::class);

        $this->dataFieldAutoMapper = new DataFieldAutoMapper(
            $this->helperMock,
            $this->dataFieldMock,
            $this->reinitableConfigMock
        );
    }

    public function testRun()
    {
        $websiteId = 1;
        $clientMock = $this->createMock(Client::class);

        $this->helperMock->expects($this->once())
            ->method('getWebsiteApiClient')
            ->with($websiteId)
            ->willReturn($clientMock);

        $contactDatafields = [
            'customer_data' => [
                [
                    'name' => 'test_name',
                    'type' => 'test_type',
                    'visibility' => 'test_visibility',
                    'automap' => true,
                ],
                [
                    'name' => 'test_name_2',
                    'type' => 'test_type_2',
                    'visibility' => 'test_visibility',
                    'automap' => true,
                ],
            ],
        ];

        $this->dataFieldMock->expects($this->once())
            ->method('getContactDatafields')
            ->with(true)
            ->willReturn($contactDatafields);

        $clientMock->expects($this->exactly(2))
            ->method('postDataFields')
            ->willReturn((object)['message' => Client::API_ERROR_DATAFIELD_EXISTS]);

        $this->helperMock->expects($this->exactly(2))
            ->method('saveConfigData');

        $this->helperMock->expects($this->exactly(2))
            ->method('log');

        $this->reinitableConfigMock->expects($this->once())
            ->method('reinit');

        $this->dataFieldAutoMapper->run($websiteId);

        $this->assertEmpty($this->dataFieldAutoMapper->getMappingErrors());
    }

    public function testRunExcludesDataFieldsThatShouldNotBeAutoMapped()
    {
        $websiteId = 1;
        $clientMock = $this->createMock(Client::class);

        $this->helperMock->expects($this->once())
            ->method('getWebsiteApiClient')
            ->with($websiteId)
            ->willReturn($clientMock);

        $contactDatafields = [
            'customer_data' => [
                [
                    'name' => 'test_name',
                    'type' => 'test_type',
                    'visibility' => 'test_visibility',
                    'automap' => true,
                ],
                [
                    'name' => 'test_name_2',
                    'type' => 'test_type_2',
                    'visibility' => 'test_visibility_2',
                    'automap' => false,
                ],
            ],
        ];

        $this->dataFieldMock->expects($this->once())
            ->method('getContactDatafields')
            ->with(true)
            ->willReturn($contactDatafields);

        $clientMock->expects($this->once())
            ->method('postDataFields')
            ->willReturn((object)['message' => Client::API_ERROR_DATAFIELD_EXISTS]);

        $this->helperMock->expects($this->once())
            ->method('saveConfigData')
            ->with(
                'connector_data_mapping/customer_data/0',
                'TEST_NAME',
                'websites',
                $websiteId
            );

        $this->helperMock->expects($this->once())
            ->method('log')
            ->with('DataFieldAutoMapper successfully mapped : test_name');

        $this->reinitableConfigMock->expects($this->once())
            ->method('reinit');

        $this->dataFieldAutoMapper->run($websiteId);

        $this->assertEmpty($this->dataFieldAutoMapper->getMappingErrors());
    }

    public function testRunWithErrorsStored()
    {
        $websiteId = 1;
        $clientMock = $this->createMock(Client::class);

        $this->helperMock->expects($this->once())
            ->method('getWebsiteApiClient')
            ->with($websiteId)
            ->willReturn($clientMock);

        $contactDatafields = [
            'customer_data' => [
                [
                    'name' => 'test_name',
                    'type' => 'test_type',
                    'visibility' => 'test_visibility',
                    'automap' => true,
                ],
            ],
        ];

        $this->dataFieldMock->expects($this->once())
            ->method('getContactDatafields')
            ->with(true)
            ->willReturn($contactDatafields);

        $clientMock->expects($this->once())
            ->method('postDataFields')
            ->willReturn((object)['message' => 'Some other API error']);

        $this->helperMock->expects($this->never())
            ->method('saveConfigData');

        $this->helperMock->expects($this->never())
            ->method('log');

        $this->reinitableConfigMock->expects($this->once())
            ->method('reinit');

        $this->dataFieldAutoMapper->run($websiteId);

        $this->assertNotEmpty($this->dataFieldAutoMapper->getMappingErrors());
    }
}
