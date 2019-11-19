<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog as ResourceCatalog;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog\Collection as CatalogCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory;
use Dotdigitalgroup\Email\Model\Sync\Catalog;
use Dotdigitalgroup\Email\Model\Sync\Catalog\CatalogSyncerInterface;
use Dotdigitalgroup\Email\Model\Sync\Catalog\CatalogSyncFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;

class CatalogTest extends TestCase
{
    /**
     * @var CatalogSyncFactory
     */
    private $catalogSyncFactoryMock;

    /**
     * @var Catalog
     */
    private $catalog;

    /**
     * @var CatalogCollection
     */
    private $catalogCollectionMock;

    /**
     * @var CollectionFactory
     */
    private $catalogCollectionFactoryMock;

    /**
     * @var Data
     */
    private $helperMock;

    /**
     * @var CatalogFactory
     */
    private $catalogResourceFactoryMock;

    /**
     * @var CatalogSyncerInterface
     */
    private $catalogSyncerInterfaceMock;

    /**
     * @var ResourceCatalog
     */
    private $resourceCatalogMock;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfigInterfaceMock;


    protected function setUp()
    {
        $this->catalogCollectionMock = $this->createMock(CatalogCollection::Class);
        $this->catalogCollectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->helperMock = $this->createMock(Data::class);
        $this->catalogSyncFactoryMock = $this->createMock(CatalogSyncFactory::class);
        $this->catalogResourceFactoryMock = $this->createMock(CatalogFactory::class);
        $this->catalogSyncerInterfaceMock = $this->createMock(CatalogSyncerInterface::class);
        $this->resourceCatalogMock = $this->createMock(ResourceCatalog::class);
        $this->scopeConfigInterfaceMock = $this->createMock(ScopeConfigInterface::class);

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
            $this->catalogSyncFactoryMock
        );
    }

    /**
     *
     */
    public function testSyncCatalogIfProductsAvailableToProcess()
    {
        $countProducts = 5;
        $removeOrphanProductsResult = null;
        $unexpectedResultMessage = 'Done.';
        $expectedResultMessage = '----------- Catalog sync ----------- : 00:00:00, Total processed = 10, Total synced = 5';

        $this->getLimit();
        $productsToProcess = $this->getMockProductsToProcess();
        $syncedProducts = $this->getMockSyncedProducts();

        $this->catalogCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->catalogCollectionMock);

        $this->catalogCollectionMock->expects($this->once())
            ->method('getProductsToProcess')
            ->willReturn($productsToProcess);

        $this->catalogResourceFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->resourceCatalogMock);

        $this->resourceCatalogMock->expects($this->atLeastOnce())
            ->method('removeOrphanProducts')
            ->willReturn($removeOrphanProductsResult);

        $this->catalogSyncFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->catalogSyncerInterfaceMock);

        $this->catalogSyncerInterfaceMock->expects($this->atLeastOnce())
            ->method('sync')
            ->willReturn($syncedProducts);

        $this->setImportedByDate(array_keys($syncedProducts));
        $this->setProcessed($productsToProcess);

        $response = $this->catalog->sync();

        $this->assertEquals($countProducts, 5);
        $this->assertNull($removeOrphanProductsResult);
        $this->assertNotEquals($response['message'], $unexpectedResultMessage);
        $this->assertEquals($response['message'], $expectedResultMessage);
    }

    /**
     *
     */
    public function testSyncCatalogIfNoProductsAvailableToProcess()
    {
        $countProducts = 0;
        $removeOrphanProductsResult = null;
        $expectedResultMessage = 'Catalog sync skipped, no products to process.';
        $unexpectedResultMessage = '----------- Catalog sync ----------- : 00:00:00, Total processed = 10, Total synced = 10';

        $this->getLimit();
        $productsToProcess = [];

        $this->catalogCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->catalogCollectionMock);

        $this->catalogCollectionMock->expects($this->once())
            ->method('getProductsToProcess')
            ->willReturn($productsToProcess);

        $this->catalogResourceFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->resourceCatalogMock);

        $this->setProcessed($productsToProcess);

        $response = $this->catalog->sync();

        $this->assertEquals($countProducts, 0);
        $this->assertNull($removeOrphanProductsResult);
        $this->assertNotEquals($response['message'], $unexpectedResultMessage);
        $this->assertEquals($response['message'], $expectedResultMessage);
    }

    /**
     * Tests retrieving the configured sync limit.
     */
    private function getLimit()
    {
        $this->scopeConfigInterfaceMock->expects($this->once())
            ->method('getValue')
            ->with(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT)
            ->willReturn(500);
    }

    /**
     * @param array $products
     */
    private function setProcessed($products)
    {
        $this->resourceCatalogMock->expects($this->once())
            ->method('setProcessedByIds')
            ->with($products);
    }

    /**
     * @param $products
     */
    private function setImportedByDate($products)
    {
        $this->resourceCatalogMock->expects($this->atLeastOnce())
            ->method('setImportedDateByIds')
            ->with($products);
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
            '1205' => [],
            '1206' => [],
            '1207' => [],
            '1208' => [],
            '1209' => []
        ];
    }
}
