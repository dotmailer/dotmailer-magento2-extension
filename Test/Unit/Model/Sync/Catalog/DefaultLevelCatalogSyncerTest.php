<?php

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog;
use Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory;
use Dotdigitalgroup\Email\Model\Sync\Catalog\DefaultLevelCatalogSyncer;
use Dotdigitalgroup\Email\Model\Sync\Catalog\StoreCatalogSyncer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;

class DefaultLevelCatalogSyncerTest extends TestCase
{
    /**
     * @var DefaultLevelCatalogSyncer
     */
    private $defaultCatalogSyncer;

    /**
     * @var Data
     */
    private $helperMock;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfigInterfaceMock;

    /**
     * @var CatalogFactory
     */
    private $catalogFactoryMock;

    /**
     * @var StoreCatalogSyncer
     */
    private $storeCatalogSyncerMock;

    /**
     * @var Catalog
     */
    private $resourceCatalogMock;

    protected function setUp()
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->scopeConfigInterfaceMock = $this->createMock(ScopeConfigInterface::class);
        $this->catalogFactoryMock = $this->createMock(CatalogFactory::class);
        $this->storeCatalogSyncerMock = $this->createMock(StoreCatalogSyncer::class);
        $this->resourceCatalogMock = $this->createMock(Catalog::class);
        $this->defaultCatalogSyncer = new DefaultLevelCatalogSyncer(
            $this->helperMock,
            $this->scopeConfigInterfaceMock,
            $this->resourceCatalogMock,
            $this->storeCatalogSyncerMock
        );
    }

    /**
     * @dataProvider getProducts
     * @param $products
     */
    public function testIfUserAndCatalogSyncAreEnabledNumberOfProductsGreaterThanZero($products)
    {
        $limit = $this->getLimit(500);
        $this->mockProducts($products, $limit);

        $this->helperMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->helperMock->expects($this->once())
            ->method('isCatalogSyncEnabled')
            ->willReturn(true);

        $this->catalogMock($products);

        $result = $this->defaultCatalogSyncer->sync();

        $this->assertNotEquals($result, 0);
        $this->assertEquals($result, count($products));
    }

    public function testIfUserIsDisabledThenSyncReturnsNull()
    {
        $this->helperMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->helperMock->expects($this->once())
            ->method('isCatalogSyncEnabled')
            ->willReturn(true);

        $this->neverExpectedMocks();

        $result = $this->defaultCatalogSyncer->sync();

        $this->assertEquals($result, 0);
    }

    public function testIfCatalogSyncIsDisabledThenSyncReturnsNull()
    {
        $this->helperMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->helperMock->expects($this->once())
            ->method('isCatalogSyncEnabled')
            ->willReturn(false);

        $this->neverExpectedMocks();

        $result = $this->defaultCatalogSyncer->sync();

        $this->assertEquals($result, 0);
    }

    public function testIfCatalogSyncAndUserAreDisabledThenSyncReturnsNull()
    {
        $this->helperMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->helperMock->expects($this->once())
            ->method('isCatalogSyncEnabled')
            ->willReturn(false);

        $this->neverExpectedMocks();

        $result = $this->defaultCatalogSyncer->sync();

        $this->assertEquals($result, 0);
    }

    /**
     * @param $limit
     * Returns the limit and test the function will be executed EXACTLY ONCE
     * @return int
     */
    private function getLimit($limit)
    {
        $this->scopeConfigInterfaceMock->expects($this->once())
            ->method('getValue')
            ->with(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT)
            ->willReturn($limit);

        return $limit;
    }

    /**
     * @param $products
     * @param $limit
     * Mocking Products Array
     * @return null
     */
    private function mockProducts($products, $limit)
    {
        $this->storeCatalogSyncerMock->expects($this->once())
            ->method('syncByStore')
            ->with(
                \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                0,
                $limit,
                'Catalog_Default'
            )
            ->willReturn($products);
    }

    /**
     * @param $products
     * Mocking the catalog
     * @return null
     */
    private function catalogMock($products)
    {
        $this->resourceCatalogMock->expects($this->once())
            ->method('setImportedByIds')
            ->with(array_keys($products));
    }

    /**
     * Here we cover the case that some function will never going to be executed
     * @return null
     */
    private function neverExpectedMocks()
    {
        $this->scopeConfigInterfaceMock->expects($this->never())
            ->method('getValue');

        $this->storeCatalogSyncerMock->expects($this->never())
            ->method('syncByStore');

        $this->resourceCatalogMock->expects($this->never())
            ->method('setImportedByIds');
    }

    /**
     * Returns possible array product combinations
     * @return array
     */
    public function getProducts()
    {
        return [
            [['Product 1','Product 2','Product 3']],
            [['Product A','Product B','Product C','Product D']],
            [['Product a','Product b']],
            [['Product X','Product Y','Product Z','Product W']]
        ];
    }
}
