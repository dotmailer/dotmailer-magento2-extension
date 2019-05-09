<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Stdlib\DateTime;
use Dotdigitalgroup\Email\Model\Config\Json;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Dotdigitalgroup\Email\Model\Importer as Import;
use PHPUnit\Framework\TestCase;

class ImporterTest extends TestCase
{
    /**
     * @var Context
     */
    private $contextMock;

    /**
     * @var Registry
     */
    private $registryMock;

    /**
     * @var Data
     */
    private $dataMock;

    /**
     * @var Importer
     */
    private $importerMock;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManagerMock;

    /**
     * @var DateTime
     */
    private $dateTimeMock;

    /**
     * @var Json
     */
    private $serializerMock;

    /**
     * @var AbstractResource
     */
    private $resourceModelMock;

    /**
     * @var AbstractDb
     */
    private $resourceCollectionMock;

    /**
     * @var Import
     */
    private $importer;

    protected function setUp()
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->registryMock = $this->createMock(Registry::class);
        $this->dataMock = $this->createMock(Data::class);
        $this->importerMock = $this->createMock(Importer::class);
        $this->objectManagerMock = $this->createMock(ObjectManagerInterface::class);
        $this->dateTimeMock = $this->createMock(DateTime::class);
        $this->serializerMock = $this->createMock(Json::class);
        $this->resourceModelMock= $this->getMockBuilder(AbstractResource::class)
            ->setMethods(['getIdFieldName'])
            ->getMockForAbstractClass();
        $this->resourceCollectionMock = $this->createMock(AbstractDb::class);

        $this->importer = new Import(
            $this->contextMock,
            $this->registryMock,
            $this->dataMock,
            $this->importerMock,
            $this->objectManagerMock,
            $this->dateTimeMock,
            $this->serializerMock,
            [],
            $this->resourceModelMock,
            $this->resourceCollectionMock
        );
    }

    public function testRegisterQueueReturnsTrueIfDataExists()
    {
        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with($this->getData())
            ->willReturn(json_encode($this->getData()));

        $this->importerMock->expects($this->once())
            ->method('save');

        $result = $this->importer->registerQueue(
            \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_CART_INSIGHT_CART_PHASE,
                $this->getData(),
                \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE,
                1
        );

        $this->assertTrue($result);
    }

    public function testRegisterQueueReturnsFalseIfDataDoNotExists()
    {
        $this->serializerMock->expects($this->never())
            ->method('serialize');

        $this->importerMock->expects($this->never())
            ->method('save');

        $result = $this->importer->registerQueue(
            \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_CART_INSIGHT_CART_PHASE,
            [],
            \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE,
            1
        );

        $this->assertFalse($result);
    }

    public function testRegisterQueueReturnsTrueIfFileExists()
    {
        $this->serializerMock->expects($this->never())
            ->method('serialize');

        $this->importerMock->expects($this->once())
            ->method('save');

        $result = $this->importer->registerQueue(
            \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_CART_INSIGHT_CART_PHASE,
           '',
            \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE,
            1,
            'path/to/file.csv'
        );

        $this->assertTrue($result);
    }

    public function testRegisterQueueReturnsFalseIfFileDoNotExists()
    {
        $this->serializerMock->expects($this->never())
            ->method('serialize');

        $this->importerMock->expects($this->never())
            ->method('save');

        $result = $this->importer->registerQueue(
            \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_CART_INSIGHT_CART_PHASE,
            '',
            \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE,
            1,
            ''
        );

        $this->assertFalse($result);
    }
    /**
     * Returns PayloadData
     * @return array
     */
    private function getData()
    {
        return $data = [
            'key' => 1,
            'contactIdentifier' => 'testContactIdentifier',
            'json' => [
                'cartId' => 1,
                'cartUrl' => 'http://sampleurl.io/cartid/12',
                'createdDate' => 'sampleDate',
                'modifiedDate' => 'sampleDate',
                'currency' => 'GBP',
                'subTotal' => '120.00',
                'taxAmount' => '20.00',
                'grandTotal' => '140.00'
            ]
        ];
    }
}
