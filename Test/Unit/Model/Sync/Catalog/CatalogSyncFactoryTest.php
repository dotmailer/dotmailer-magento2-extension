<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Catalog;

use Dotdigitalgroup\Email\Model\Sync\Catalog\CatalogSyncFactory;
use Dotdigitalgroup\Email\Model\Sync\Catalog\DefaultLevelCatalogSyncer;
use Dotdigitalgroup\Email\Model\Sync\Catalog\DefaultLevelCatalogSyncerFactory;
use Dotdigitalgroup\Email\Model\Sync\Catalog\StoreLevelCatalogSyncer;
use Dotdigitalgroup\Email\Model\Sync\Catalog\StoreLevelCatalogSyncerFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;

class CatalogSyncFactoryTest extends TestCase
{
    /**
     * @var CatalogSyncFactory
     */
    private $catalogSyncFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfigInterfaceMock;

    /**
     * @var DefaultLevelCatalogSyncerFactory
     */
    private $defaultLevelCatalogSyncerFactoryMock;

    /**
     * @var StoreLevelCatalogSyncerFactory
     */
    private $storeLevelCatalogSyncerFactoryMock;

    /**
     * @var DefaultLevelCatalogSyncerFactory
     */
    private $defaultLevelCatalogSyncerMock;

    /**
     * @var StoreLevelCatalogSyncerFactory
     */
    private $storeLevelCatalogSyncerMock;

    protected function setUp()
    {
        $this->scopeConfigInterfaceMock = $this->createMock(ScopeConfigInterface::class);
        $this->defaultLevelCatalogSyncerFactoryMock = $this->createMock(DefaultLevelCatalogSyncerFactory::class);
        $this->storeLevelCatalogSyncerFactoryMock = $this->createMock(StoreLevelCatalogSyncerFactory::class);
        $this->defaultLevelCatalogSyncerMock = $this->createMock(DefaultLevelCatalogSyncer::class);
        $this->storeLevelCatalogSyncerMock = $this->createMock(StoreLevelCatalogSyncer::class);

        $this->catalogSyncFactory = new CatalogSyncFactory(
            $this->scopeConfigInterfaceMock,
            $this->defaultLevelCatalogSyncerFactoryMock,
            $this->storeLevelCatalogSyncerFactoryMock
        );
    }

    public function testCreateClassForDefaultLevel()
    {
        $this->mockSyncLevel(1);

        $this->defaultLevelCatalogSyncerFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->defaultLevelCatalogSyncerMock);

        $this->storeLevelCatalogSyncerFactoryMock->expects($this->never())
            ->method('create');

        $result = $this->catalogSyncFactory->create();

        $this->assertEquals($result, $this->defaultLevelCatalogSyncerMock);
    }

    public function testCreateClassForStoreLevel()
    {
        $this->mockSyncLevel(2);

        $this->defaultLevelCatalogSyncerFactoryMock->expects($this->never())
            ->method('create');

        $this->storeLevelCatalogSyncerFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->storeLevelCatalogSyncerMock);

        $result = $this->catalogSyncFactory->create();

        $this->assertEquals($result, $this->storeLevelCatalogSyncerMock);
    }

    /**
     * @dataProvider getInvalidSyncLevel
     * @param int
     */
    public function testThatIfSyncLevelFailsToBeDefinedAsStoredOrDefaultLevel($syncLevel)
    {
        $this->mockSyncLevel($syncLevel);

        $this->defaultLevelCatalogSyncerFactoryMock->expects($this->never())
            ->method('create');

        $this->storeLevelCatalogSyncerFactoryMock->expects($this->never())
            ->method('create');

        $result = $this->catalogSyncFactory->create();

        $this->assertNull($result);
    }

    private function mockSyncLevel($syncLevel)
    {
        $this->scopeConfigInterfaceMock->expects($this->once())
            ->method('getValue')
            ->with(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CATALOG_VALUES)
            ->willReturn($syncLevel);
    }

    /**
     * Return possible values that are not going to be accepted
     * @return array
     */
    public function getInvalidSyncLevel()
    {
        return [
            [0],
            [3],
            [9],
            [150]
        ];
    }
}
