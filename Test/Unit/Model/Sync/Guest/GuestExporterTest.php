<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Guest;

use Dotdigitalgroup\Email\Model\Connector\ContactData;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection as ContactCollection;
use Dotdigitalgroup\Email\Model\Sync\Export\CsvHandler;
use Dotdigitalgroup\Email\Model\Sync\Guest\GuestExporter;
use PHPUnit\Framework\TestCase;

class GuestExporterTest extends TestCase
{
    /**
     * @var ContactData|ContactData&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactDataMock;

    /**
     * @var CsvHandler|CsvHandler&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $csvHandlerMock;

    /**
     * @var ContactCollection|ContactCollection&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactCollectionMock;

    /**
     * @var Contact|Contact&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactMock;

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
        $this->contactDataMock = $this->createMock(ContactData::class);
        $this->csvHandlerMock = $this->createMock(CsvHandler::class);
        $this->contactCollectionMock = $this->createMock(ContactCollection::class);
        $this->contactMock = $this->getMockBuilder(Contact::class)
            ->addMethods(['getEmailContactId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->guestExporter = new GuestExporter(
            $this->csvHandlerMock,
            $this->contactDataMock,
        );
    }

    public function testGuestExporter()
    {
        $this->contactMock->expects($this->atLeastOnce())
            ->method('getEmailContactId')
            ->willReturn(1);

        $this->contactDataMock->expects($this->atLeastOnce())
            ->method('init')
            ->willReturn($this->contactDataMock);

        $this->contactDataMock->expects($this->atLeastOnce())
            ->method('setContactData')
            ->willReturn($this->contactDataMock);

        $this->contactDataMock->expects($this->atLeastOnce())
            ->method('toCSVArray')
            ->willReturn([]);

        $data = $this->guestExporter->export($this->getGuestsForSync());

        $this->assertEquals(count($data), count($this->guests));
    }

    /**
     * @return Contact[]
     */
    private function getGuestsForSync()
    {
        for ($i = 0; $i < rand(1, 20); $i++) {
            $this->guests += [$this->contactMock];
        }

        return $this->guests;
    }
}
