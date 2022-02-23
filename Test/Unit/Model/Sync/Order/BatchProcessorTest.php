<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Order;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Catalog\UpdateCatalogBulk;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\OrderFactory as OrderResourceFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Order as OrderResource;
use Dotdigitalgroup\Email\Model\Sync\Order\BatchProcessor;
use PHPUnit\Framework\TestCase;

class BatchProcessorTest extends TestCase
{
    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var ImporterFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $importerFactoryMock;

    /**
     * @var UpdateCatalogBulk|\PHPUnit\Framework\MockObject\MockObject
     */
    private $updateCatalogBulkMock;

    /**
     * @var Importer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $importerMock;

    /**
     * @var OrderResourceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderResourceFactoryMock;

    /**
     * @var OrderResource|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderResourceMock;

    /**
     * @var BatchProcessor
     */
    private $batchProcessor;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->updateCatalogBulkMock = $this->createMock(UpdateCatalogBulk::class);
        $this->importerMock = $this->createMock(Importer::class);
        $this->importerFactoryMock = $this->createMock(ImporterFactory::class);
        $this->orderResourceFactoryMock = $this->createMock(OrderResourceFactory::class);
        $this->orderResourceMock = $this->createMock(OrderResource::class);

        $this->batchProcessor = new BatchProcessor(
            $this->loggerMock,
            $this->updateCatalogBulkMock,
            $this->importerFactoryMock,
            $this->orderResourceFactoryMock
        );
    }

    public function testBatchProcessor()
    {
        $this->importerFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->importerMock);
        
        $this->importerMock->expects($this->atLeastOnce())
            ->method('registerQueue')
            ->willReturn(true);
        
        $this->loggerMock->expects($this->atLeastOnce())
            ->method('info');
        
        $this->updateCatalogBulkMock->expects($this->atLeastOnce())
            ->method('execute');
        
        $this->orderResourceFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->orderResourceMock);

        $this->orderResourceMock->expects($this->atLeastOnce())
            ->method('setImportedDateByIds');

        $this->batchProcessor->process($this->getOrdersBatch());
    }

    /**
     * Mocked Batched orders .
     *
     * @return array[]
     */
    private function getOrdersBatch()
    {
        return [
                [
                    'id' => "000001",
                    'email' => "testorder@emailsim.io",
                    "quoteId" => 1,
                    "products" => [
                        "name" => "Chaz Kangaroo",
                        "sku" => "CHAZ_HD"
                    ]
                ],
                [
                'id' => "000002",
                'email' => "testorder2@emailsim.io",
                "quoteId" => 2,
                "products" => [
                    "name" => "Chaz Kangaroo 2",
                    "sku" => "CHAZ_HD_2"
                ]
                ]
            ];
    }
}
