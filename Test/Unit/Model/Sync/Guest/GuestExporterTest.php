<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Guest;

use Dotdigital\V3\Models\Contact as SdkContact;
use Dotdigitalgroup\Email\Model\Connector\ContactData;
use Dotdigitalgroup\Email\Model\Connector\ContactDataFactory;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Sync\Export\CsvHandler;
use Dotdigitalgroup\Email\Model\Sync\Export\SdkContactBuilder;
use Dotdigitalgroup\Email\Model\Sync\Guest\GuestExporter;
use Magento\Store\Api\Data\WebsiteInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GuestExporterTest extends TestCase
{
    /**
     * @var ContactDataFactory|ContactDataFactory&MockObject|MockObject
     */
    private $contactDataFactoryMock;

    /**
     * @var CsvHandler|CsvHandler&MockObject|MockObject
     */
    private $csvHandlerMock;

    /**
     * @var SdkContactBuilder|MockObject
     */
    private $sdkContactBuilderMock;

    /**
     * @var Contact|Contact&MockObject|MockObject
     */
    private $contactMock;

    /**
     * @var WebsiteInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteInterfaceMock;

    /**
     * @var array
     */
    private $guests = [];

    /**
     * @var GuestExporter
     */
    private $guestExporter;

    protected function setUp(): void
    {
        $this->contactDataFactoryMock = $this->createMock(ContactDataFactory::class);
        $this->csvHandlerMock = $this->createMock(CsvHandler::class);
        $this->sdkContactBuilderMock = $this->createMock(SdkContactBuilder::class);
        $this->contactMock = $this->getMockBuilder(Contact::class)
            ->addMethods(['getEmailContactId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->websiteInterfaceMock = $this->createMock(WebsiteInterface::class);

        $this->guestExporter = new GuestExporter(
            $this->csvHandlerMock,
            $this->contactDataFactoryMock,
            $this->sdkContactBuilderMock
        );
    }

    public function testGuestExporter()
    {
        $contactDataMock = $this->createMock(ContactData::class);
        $sdkContactMock = $this->createMock(SdkContact::class);

        $this->contactMock->expects($this->atLeastOnce())
            ->method('getEmailContactId')
            ->willReturn(1);

        $this->contactDataFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($contactDataMock);

        $contactDataMock->expects($this->atLeastOnce())
            ->method('init')
            ->willReturn($contactDataMock);

        $contactDataMock->expects($this->atLeastOnce())
            ->method('setContactData')
            ->willReturn($contactDataMock);

        $this->sdkContactBuilderMock->expects($this->atLeastOnce())
            ->method('createSdkContact')
            ->willReturn($sdkContactMock);

        $data = $this->guestExporter->export(
            $this->getGuestsForSync(),
            $this->websiteInterfaceMock,
            123456
        );

        $this->assertEquals(count($data), count($this->guests));
    }

    /**
     * @return Contact[]
     */
    private function getGuestsForSync()
    {
        $randomQuantity = 5;
        for ($i = 0; $i < $randomQuantity; $i++) {
            $this->guests += [$this->contactMock];
        }

        return $this->guests;
    }
}
