<?php

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog;
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
        $this->storeCatalogSyncerMock = $this->createMock(StoreCatalogSyncer::class);
        $this->resourceCatalogMock = $this->createMock(Catalog::class);
        $this->defaultCatalogSyncer = new DefaultLevelCatalogSyncer(
            $this->helperMock,
            $this->storeCatalogSyncerMock
        );
    }

    /**
     * @dataProvider getProducts
     * @param $products
     */
    public function testIfUserAndCatalogSyncAreEnabledNumberOfProductsGreaterThanZero($products)
    {
        $this->mockProducts($products);

        $this->helperMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->helperMock->expects($this->once())
            ->method('isCatalogSyncEnabled')
            ->willReturn(true);

        $result = $this->defaultCatalogSyncer->sync($products);

        $this->assertNotEmpty($result);
        $this->assertEquals($result, $products);
    }

    /**
     * @dataProvider getProducts
     * @param $products
     */
    public function testIfUserIsDisabledThenSyncReturnsNull($products)
    {
        $this->helperMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->helperMock->expects($this->once())
            ->method('isCatalogSyncEnabled')
            ->willReturn(true);

        $this->neverExpectedMocks();

        $result = $this->defaultCatalogSyncer->sync($products);

        $this->assertEmpty($result);
    }

    /**
     * @dataProvider getProducts
     * @param $products
     */
    public function testIfCatalogSyncIsDisabledThenSyncReturnsNull($products)
    {
        $this->helperMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->helperMock->expects($this->once())
            ->method('isCatalogSyncEnabled')
            ->willReturn(false);

        $this->neverExpectedMocks();

        $result = $this->defaultCatalogSyncer->sync($products);

        $this->assertEmpty($result);
    }

    /**
     * @dataProvider getProducts
     * @param $products
     */
    public function testIfCatalogSyncAndUserAreDisabledThenSyncReturnsNull($products)
    {
        $this->helperMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->helperMock->expects($this->once())
            ->method('isCatalogSyncEnabled')
            ->willReturn(false);

        $this->neverExpectedMocks();

        $result = $this->defaultCatalogSyncer->sync($products);

        $this->assertEmpty($result);
    }

    /**
     * @param $products
     * Mocking Products Array
     * @return null
     */
    private function mockProducts($products)
    {
        $this->storeCatalogSyncerMock->expects($this->once())
            ->method('syncByStore')
            ->with(
                $products,
                \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                0,
                'Catalog_Default'
            )
            ->willReturn($products);
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
            ->method('setProcessedByIds');
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
