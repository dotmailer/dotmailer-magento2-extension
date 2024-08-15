<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Export;

use Dotdigital\V3\Models\Contact\Identifiers;
use Dotdigitalgroup\Email\Model\Contact;
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

    /**
     * @var ContactData|\PHPUnit\Framework\MockObject\MockObject
     */
    private $connectorModelMock;

    /**
     * @var Contact|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactMock;

    protected function setUp(): void
    {
        $this->dataFieldMapperMock = $this->createMock(DataFieldMapper::class);

        $this->connectorModelMock = $this->createMock(ContactData::class);
        $this->contactMock = $this->getMockBuilder(Contact::class)
            ->disableOriginalConstructor()
            ->addMethods(['getEmail'])
            ->getMock();

        $this->contactMock->method('getEmail')->willReturn('test@example.com');
        $this->connectorModelMock->method('getModel')->willReturn($this->contactMock);
        $this->connectorModelMock->method('getContactData')->willReturn([]);

        $this->sdkContactBuilder = new SdkContactBuilder(
            $this->dataFieldMapperMock
        );
    }

    public function testCreateSdkContact()
    {
        $columns = ['email', 'name'];
        $listId = 1;

        $this->dataFieldMapperMock
            ->expects($this->once())
            ->method('mapFields')
            ->with([], $columns)
            ->willReturn([]);

        $sdkContact = $this->sdkContactBuilder->createSdkContact($this->connectorModelMock, $columns, $listId);

        $this->assertInstanceOf(SdkContact::class, $sdkContact);
        $this->assertEquals('email', $sdkContact->getMatchIdentifier());
        $this->assertEquals(new Identifiers(['email' => 'test@example.com']), $sdkContact->getIdentifiers());
        $this->assertEquals([$listId], $sdkContact->getLists());
        $this->assertEquals(null, $sdkContact->getDataFields());
    }

    public function testCreateSdkContactWithOptInType()
    {
        $columns = ['email', 'name'];
        $listId = 1;
        $optInType = 'double';

        $this->dataFieldMapperMock
            ->expects($this->once())
            ->method('mapFields')
            ->with([], $columns)
            ->willReturn([]);

        $sdkContact = $this->sdkContactBuilder->createSdkContact(
            $this->connectorModelMock,
            $columns,
            $listId,
            $optInType
        );

        $this->assertInstanceOf(SdkContact::class, $sdkContact);
        $this->assertEquals('email', $sdkContact->getMatchIdentifier());
        $this->assertEquals(new Identifiers(['email' => 'test@example.com']), $sdkContact->getIdentifiers());
        $this->assertEquals([$listId], $sdkContact->getLists());
        $this->assertEquals($optInType, $sdkContact->getChannelProperties()->getEmail()->getOptInType());
        $this->assertEquals(null, $sdkContact->getDataFields());
    }
}
