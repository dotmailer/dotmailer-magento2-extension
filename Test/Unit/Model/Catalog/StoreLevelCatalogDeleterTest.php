<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Catalog;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Catalog;
use Dotdigitalgroup\Email\Model\CatalogFactory;
use Dotdigitalgroup\Email\Model\Catalog\StoreLevelCatalogDeleter;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class StoreLevelCatalogDeleterTest extends TestCase
{
    /** @var MockObject */
    private $scopeConfig;

    /** @var MockObject */
    private $helper;

    /** @var MockObject */
    private $storeManager;

    /** @var MockObject */
    private $catalogFactory;

    /** @var MockObject */
    private $importerFactory;

    /** @var StoreLevelCatalogDeleter */
    private $deleter;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->helper = $this->createMock(Data::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->catalogFactory = $this->createMock(CatalogFactory::class);
        $this->importerFactory = $this->createMock(ImporterFactory::class);

        $this->deleter = new StoreLevelCatalogDeleter(
            $this->helper,
            $this->scopeConfig,
            $this->storeManager,
            $this->catalogFactory,
            $this->importerFactory
        );
    }

    private function configure(int $syncLevel, bool $imported = true): void
    {
        $this->scopeConfig->method('getValue')->willReturnOnConsecutiveCalls(1, $syncLevel);
        $this->helper->method('isCatalogSyncEnabled')->willReturn(true);

        $catalog = $this->createMock(Catalog::class);
        $catalog->method('loadProductById')->willReturnSelf();
        $catalog->method('__call')->with('getLastImportedAt')->willReturn($imported ? '2026-01-01' : null);
        $this->catalogFactory->method('create')->willReturn($catalog);
    }

    private function store(int $websiteId, string $storeCode): MockObject
    {
        $store = $this->createMock(Store::class);
        $store->method('getWebsiteId')->willReturn($websiteId);
        $store->method('getCode')->willReturn($storeCode);

        return $store;
    }

    public function testQueuesDeleteOnlyForGivenWebsites()
    {
        $this->configure(2);
        $this->storeManager->method('getStores')->willReturn([
            $this->store(1, 'default'),
            $this->store(2, 'second_view'),
        ]);
        $website = $this->createMock(Website::class);
        $website->method('getCode')->willReturn('second');
        $this->storeManager->method('getWebsite')->with(2)->willReturn($website);

        $importer = $this->createMock(Importer::class);
        $this->importerFactory->method('create')->willReturn($importer);
        $importer->expects($this->once())
            ->method('registerQueue')
            ->with('Catalog_second_second_view', [5], Importer::MODE_SINGLE_DELETE, 2);

        $this->deleter->execute(5, [2]);
    }

    public function testDoesNothingAtDefaultLevel()
    {
        $this->configure(1);
        $this->importerFactory->expects($this->never())->method('create');

        $this->deleter->execute(5, [2]);
    }

    public function testDoesNothingWhenProductNeverImported()
    {
        $this->configure(2, false);
        $this->importerFactory->expects($this->never())->method('create');

        $this->deleter->execute(5, [2]);
    }
}
