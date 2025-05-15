<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync;

use Dotdigitalgroup\Email\Api\Model\Sync\Batch\BatchMergerInterface;
use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog as ResourceCatalog;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog\Collection as CatalogCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory;
use Dotdigitalgroup\Email\Model\Sync\Batch\MegaBatchProcessor;
use Dotdigitalgroup\Email\Model\Sync\Batch\MegaBatchProcessorFactory;
use Dotdigitalgroup\Email\Model\Sync\Catalog;
use Dotdigitalgroup\Email\Model\Sync\Catalog\CatalogSyncerInterface;
use Dotdigitalgroup\Email\Model\Sync\Catalog\CatalogSyncFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use Dotdigitalgroup\Email\Model\Importer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CatalogTest extends TestCase
{
    /**
     * @var CatalogSyncFactory|MockObject
     */
    private $catalogSyncFactoryMock;

    /**
     * @var Catalog|MockObject
     */
    private $catalog;

    /**
     * @var CatalogCollection|MockObject
     */
    private $catalogCollectionMock;

    /**
     * @var CollectionFactory|MockObject
     */
    private $catalogCollectionFactoryMock;

    /**
     * @var Data|MockObject
     */
    private $helperMock;

    /**
     * @var CatalogFactory|MockObject
     */
    private $catalogResourceFactoryMock;

    /**
     * @var CatalogSyncerInterface|MockObject
     */
    private $catalogSyncerInterfaceMock;

    /**
     * @var ResourceCatalog|MockObject
     */
    private $resourceCatalogMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigInterfaceMock;

    /**
     * @var ImporterFactory|MockObject
     */
    private $importerFactoryMock;

    /**
     * @var Importer|mixed|MockObject
     */
    private $importerMock;

    /**
     * @var MegaBatchProcessorFactory|MockObject
     */
    private $megaBatchProcessorFactoryMock;

    /**
     * @var BatchMergerInterface|MockObject
     */
    private $mergeManagerMock;

    protected function setUp() :void
    {
        $this->catalogCollectionMock = $this->createMock(CatalogCollection::Class);
        $this->catalogCollectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->helperMock = $this->createMock(Data::class);
        $this->catalogSyncFactoryMock = $this->createMock(CatalogSyncFactory::class);
        $this->catalogResourceFactoryMock = $this->createMock(CatalogFactory::class);
        $this->catalogSyncerInterfaceMock = $this->createMock(CatalogSyncerInterface::class);
        $this->resourceCatalogMock = $this->createMock(ResourceCatalog::class);
        $this->scopeConfigInterfaceMock = $this->createMock(ScopeConfigInterface::class);
        $this->megaBatchProcessorFactoryMock = $this->createMock(MegaBatchProcessorFactory::class);
        $this->mergeManagerMock = $this->createMock(BatchMergerInterface::class);

        $this->helperMock->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true);

        $this->helperMock->expects($this->any())
            ->method('isCatalogSyncEnabled')
            ->willReturn(true);

        $this->catalog = new Catalog(
            $this->helperMock,
            $this->scopeConfigInterfaceMock,
            $this->catalogResourceFactoryMock,
            $this->catalogCollectionFactoryMock,
            $this->catalogSyncFactoryMock,
            $this->megaBatchProcessorFactoryMock,
            $this->mergeManagerMock,
        );
    }

    /**
     *
     */
    public function testSyncCatalogIfProductsAvailableToProcess()
    {
        $countProducts = 5;
        $unexpectedResultMessage = 'Done.';
        $expectedResultMessage = '----------- Catalog sync ----------- : ' .
            '00:00:00, Total processed = 10, Total synced = 15';

        $this->getLimitAndBreakValue();
        $productsToProcess = $this->getMockProductsToProcess();
        $noProductsTProcess = [];
        $syncedProducts = $this->getMockSyncedProducts();

        $this->catalogCollectionFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->catalogCollectionMock);

        $this->catalogCollectionMock
            ->method('getUnprocessedProducts')
            ->willReturnOnConsecutiveCalls($productsToProcess, $noProductsTProcess);

        $this->catalogResourceFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->resourceCatalogMock);

        $this->catalogSyncFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->catalogSyncerInterfaceMock);

        $this->catalogSyncerInterfaceMock->expects($this->atLeastOnce())
            ->method('sync')
            ->willReturn($syncedProducts);

        $this->mergeManagerMock->expects($this->once())
            ->method('mergeBatch')
            ->willReturn($this->getMockSyncedProducts());

        $megaBatchProcessorMock = $this->createMock(MegaBatchProcessor::class);
        $this->megaBatchProcessorFactoryMock->expects($this->exactly(3))
            ->method('create')
            ->willReturn($megaBatchProcessorMock);

        $megaBatchProcessorMock->expects($this->exactly(3))
            ->method('process');

        $response = $this->catalog->sync();

        $this->assertEquals($countProducts, 5);
        $this->assertNotEquals($response['message'], $unexpectedResultMessage);
        $this->assertEquals($response['message'], $expectedResultMessage);
    }

    /**
     * Tests retrieving the configured sync limit.
     */
    private function getLimitAndBreakValue()
    {
        $matcher = $this->exactly(2);
        $this->scopeConfigInterfaceMock->method('getValue')
            ->willReturnCallback(function () use ($matcher) {
                return match ($matcher->getInvocationCount()) {
                    1 => [Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT],
                    2 => [Config::XML_PATH_CONNECTOR_SYNC_BREAK_VALUE],
                    3 => [Config::XML_PATH_CONNECTOR_MEGA_BATCH_SIZE_CATALOG]
                };
            })
            ->willReturnOnConsecutiveCalls(500, null, 2500);
    }

    /**
     * Returns product array
     *
     * @return array
     */
    public function getMockProductsToProcess()
    {
        return [
            0 => '1205',
            1 => '1206',
            2 => '1207',
            3 => '1208',
            4 => '1209',
            5 => '1210',
            6 => '1211',
            7 => '1212',
            8 => '1213',
            9 => '1214'
        ];
    }

    /**
     * Returns product array
     *
     * @return array
     */
    public function getMockSyncedProducts()
    {
        return [
            'Catalog_Store_1' => [
                'products' => [
                    '1205' => [],
                    '1206' => [],
                    '1207' => [],
                    '1208' => [],
                    '1209' => []
                ],
                'websiteId' => 1
            ],
            'Catalog_Store_2' => [
                'products' => [
                    '1205' => [],
                    '1206' => [],
                    '1207' => [],
                    '1208' => [],
                    '1209' => []
                ],
                'websiteId' => 2
            ],
            'Catalog_Store_3' => [
                'products' => [
                    '1205' => [],
                    '1206' => [],
                    '1207' => [],
                    '1208' => [],
                    '1209' => []
                ],
                'websiteId' => 3
            ],
        ];
    }
}
