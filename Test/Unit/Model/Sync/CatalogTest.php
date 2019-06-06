<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Connector\ProductFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog as ResourceCatalog;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory;
use Dotdigitalgroup\Email\Model\Sync\Catalog;
use Dotdigitalgroup\Email\Model\Sync\Catalog\CatalogSyncerInterface;
use Dotdigitalgroup\Email\Model\Sync\Catalog\CatalogSyncFactory;
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
     * @var ProductFactory
     */
    private $connectorProductFactoryMock;

    /**
     * @var CatalogSyncerInterface
     */
    private $catalogSyncerInterfaceMock;

    /**
     * @var ResourceCatalog
     */
    private $resourceCatalogMock;

    protected function setUp()
    {
        $this->catalogCollectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->connectorProductFactoryMock = $this->createMock(ProductFactory::class);
        $this->helperMock = $this->createMock(Data::class);
        $this->catalogSyncFactoryMock = $this->createMock(CatalogSyncFactory::class);
        $this->catalogResourceFactoryMock = $this->createMock(CatalogFactory::class);
        $this->catalogSyncerInterfaceMock = $this->createMock(CatalogSyncerInterface::class);
        $this->resourceCatalogMock = $this->createMock(ResourceCatalog::class);

        $this->catalog = new Catalog(
            $this->helperMock,
            $this->catalogResourceFactoryMock,
            $this->catalogSyncFactoryMock
        );
    }

    public function testSyncCatalogFunctionIfFoundProductsExists()
    {
        $countProducts = 43;
        $scopeValue = 2;
        $removeOrphanProductsResult = null;
        $unexpectedResultMessage = 'Done.';
        $expectedResultMessage = '----------- Catalog sync ----------- : 00:00:00, Total synced = 43';
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
            ->willReturn($countProducts);

        $response = $this->catalog->sync();
        $this->assertEquals($countProducts, 43);
        $this->assertEquals($scopeValue, 2);
        $this->assertNull($removeOrphanProductsResult);
        $this->assertNotEquals($response['message'], $unexpectedResultMessage);
        $this->assertEquals($response['message'], $expectedResultMessage);
    }

    public function testSyncCatalogFunctionIfFoundProductsNotExists()
    {
        $countProducts = 0;
        $scopeValue = 2;
        $removeOrphanProductsResult = null;
        $expectedResultMessage = 'Done.';
        $unexpectedResultMessage = '----------- Catalog sync ----------- : 00:00:00, Total synced = 0';
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
            ->willReturn($countProducts);

        $response = $this->catalog->sync();
        $this->assertEquals($countProducts, 0);
        $this->assertEquals($scopeValue, 2);
        $this->assertNull($removeOrphanProductsResult);
        $this->assertNotEquals($response['message'], $unexpectedResultMessage);
        $this->assertEquals($response['message'], $expectedResultMessage);
    }
}
