<?php

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory;
use Dotdigitalgroup\Email\Model\Sync\Catalog\Exporter;
use Dotdigitalgroup\Email\Model\Sync\Catalog\StoreCatalogSyncer;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;

class StoreCatalogSyncerTest extends TestCase
{
    /**
     * @var StoreCatalogSyncer;
     */
    private $storeCatalogSyncer;

    /**
     * @var ImporterFactory
     */
    private $importerFactoryMock;

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
    private $catalogResourceFactoryMock;

    /**
     * @var Exporter
     */
    private $exporterMock;
    /**
     * @var Importer
     */
    private $importerModelMock;
    protected function setUp()
    {
        $this->importerFactoryMock = $this->createMock(ImporterFactory::class);
        $this->helperMock = $this->createMock(Data::class);
        $this->scopeConfigInterfaceMock = $this->createMock(ScopeConfigInterface::class);
        $this->catalogResourceFactoryMock = $this->createMock(CatalogFactory::class);
        $this->exporterMock = $this->createMock(Exporter::class);
        $this->importerModelMock = $this->createMock(Importer::class);

        $this->storeCatalogSyncer = new StoreCatalogSyncer(
            $this->importerFactoryMock,
            $this->helperMock,
            $this->exporterMock
        );
    }

    /**
     * @dataProvider getParameters
     * @param $storeId
     * @param $websiteId
     * @param $limit
     * @param $importType
     */
    public function testThatIfImporterIsSuccessThenSyncByStoreReturnsTheProducts($storeId, $websiteId, $limit, $importType)
    {
        $products = $this->getMockProducts();

        $this->exporterMock->expects($this->once())
            ->method('exportCatalog')
            ->with($storeId, $limit)
            ->willReturn($products);

        $this->importerFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->importerModelMock);

        $this->importerModelMock->expects($this->once())
            ->method('registerQueue')
            ->with(
                $importType,
                $products,
                \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
                $websiteId
            )->willReturn(true);

        $result = $this->storeCatalogSyncer->syncByStore($storeId, $websiteId, $limit, $importType);

        $this->assertEquals($result, $products);
    }

    /**
     * @dataProvider getParameters
     * @param $storeId
     * @param $websiteId
     * @param $limit
     * @param $importType
     */
    public function testThatIfImporterFailsThenSyncByStoreReturnsEmptyArray($storeId, $websiteId, $limit, $importType)
    {
        $products = $this->getMockProducts();

        $this->exporterMock->expects($this->once())
            ->method('exportCatalog')
            ->with($storeId, $limit)
            ->willReturn($products);

        $this->importerFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->importerModelMock);

        $this->importerModelMock->expects($this->once())
            ->method('registerQueue')
            ->with(
                $importType,
                $products,
                \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
                $websiteId
            )->willReturn(false);

        $result = $this->storeCatalogSyncer->syncByStore($storeId, $websiteId, $limit, $importType);

        $this->assertEquals($result, []);
    }

    /**
     * Initializes The Variables
     * @return array
     */
    public function getParameters()
    {
        return [
            [1,1,200,'subscribers'],
            [2,1,400,'catalog'],
            [3,2,4500,'contacts']
        ];
    }

    /**
     * Generates a random array of Mocked Products
     * @return array
     */
    private function getMockProducts()
    {
        $limit = rand(2, 10);
        $products = [];
        for ($i=1; $i<$limit; $i++) {
            $products[] = $this->createMock(Product::class)->toArray();
        }
        return $products;
    }
}
