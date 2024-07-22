<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Export;

use Dotdigital\V3\Models\Contact\Identifiers;
use Dotdigitalgroup\Email\Model\Sync\Export\SdkContactBuilder;
use Dotdigitalgroup\Email\Model\Connector\ContactData;
use Dotdigital\V3\Models\Contact as SdkContact;
use Dotdigitalgroup\Email\Model\Sync\Export\DataFieldMapper;
use PHPUnit\Framework\TestCase;

class SdkContactBuilderTest extends TestCase
{
    /**
     * @var SdkContactBuilder
     */
    private $sdkContactBuilder;

    /**
     * @var DataFieldMapper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dataFieldMapperMock;

    protected function setUp(): void
    {
        $this->dataFieldMapperMock = $this->createMock(DataFieldMapper::class);

        $this->sdkContactBuilder = new SdkContactBuilder(
            $this->dataFieldMapperMock
        );
    }

    public function testCreateSdkContact()
    {
        $connectorModelMock = $this->createMock(ContactData::class);
        $contactMock = $this->getMockBuilder(\Dotdigitalgroup\Email\Model\Contact::class)
            ->disableOriginalConstructor()
            ->addMethods(['getEmail'])
            ->getMock();

        $contactMock->method('getEmail')->willReturn('test@example.com');
        $connectorModelMock->method('getModel')->willReturn($contactMock);
        $connectorModelMock->method('getContactData')->willReturn([]);

        $columns = ['email', 'name'];
        $listId = 1;

        $this->dataFieldMapperMock
            ->expects($this->once())
            ->method('mapFields')
            ->with([], $columns)
            ->willReturn([]);

        $sdkContact = $this->sdkContactBuilder->createSdkContact($connectorModelMock, $columns, $listId);

        $this->assertInstanceOf(SdkContact::class, $sdkContact);
        $this->assertEquals('email', $sdkContact->getMatchIdentifier());
        $this->assertEquals(new Identifiers(['email' => 'test@example.com']), $sdkContact->getIdentifiers());
        $this->assertEquals([$listId], $sdkContact->getLists());
        $this->assertEquals(null, $sdkContact->getDataFields());
    }
}
