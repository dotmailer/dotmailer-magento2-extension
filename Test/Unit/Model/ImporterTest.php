<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\CollectionFactory;
use Magento\Framework\Stdlib\DateTime;
use Dotdigitalgroup\Email\Model\Config\Json;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Helper\Data;
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
    private $helperMock;

    /**
     * @var Importer
     */
    private $importerMock;

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
     * @var CollectionFactory
     */
    private $collectionFactoryMock;

    /**
     * @var ImporterModel
     */
    private $importer;

    protected function setUp()
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->registryMock = $this->createMock(Registry::class);
        $this->importerMock = $this->createMock(Importer::class);
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->dateTimeMock = $this->createMock(DateTime::class);
        $this->serializerMock = $this->createMock(Json::class);
        $this->resourceModelMock= $this->getMockBuilder(AbstractResource::class)
            ->setMethods(['getIdFieldName'])
            ->getMockForAbstractClass();
        $this->resourceCollectionMock = $this->createMock(AbstractDb::class);
        $this->helperMock = $this->createMock(Data::class);

        $this->importer = new ImporterModel(
            $this->contextMock,
            $this->registryMock,
            $this->importerMock,
            $this->collectionFactoryMock,
            $this->dateTimeMock,
            $this->serializerMock,
            $this->helperMock,
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
            ImporterModel::IMPORT_TYPE_CART_INSIGHT_CART_PHASE,
                $this->getData(),
            ImporterModel::MODE_SINGLE,
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
            ImporterModel::IMPORT_TYPE_CART_INSIGHT_CART_PHASE,
            [],
            ImporterModel::MODE_SINGLE,
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
            ImporterModel::IMPORT_TYPE_CART_INSIGHT_CART_PHASE,
           '',
            ImporterModel::MODE_SINGLE,
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
            ImporterModel::IMPORT_TYPE_CART_INSIGHT_CART_PHASE,
            '',
            ImporterModel::MODE_SINGLE,
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
