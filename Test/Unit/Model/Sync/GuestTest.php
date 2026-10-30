<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Customer\RegistrationStoreResolver;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection as ContactCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\Batch\MegaBatchProcessor;
use Dotdigitalgroup\Email\Model\Sync\Batch\MegaBatchProcessorFactory;
use Dotdigitalgroup\Email\Model\Sync\Batch\MergeManager;
use Dotdigitalgroup\Email\Model\Sync\Customer\ListCohortResolver;
use Dotdigitalgroup\Email\Model\Sync\Guest;
use Dotdigitalgroup\Email\Model\Sync\Guest\GuestExporter;
use Dotdigitalgroup\Email\Model\Sync\Guest\GuestExporterFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\Website;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class GuestTest extends TestCase
{
    public function testStoreCohortsAreExportedToTheirOwnListsAndImportedTogether()
    {
        $website = $this->createStub(Website::class);
        $website->method('getId')->willReturn(1);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getWebsites')->willReturn([$website]);

        $helper = $this->createStub(Data::class);
        $helper->method('isEnabled')->willReturn(true);
        $helper->method('isGuestSyncEnabled')->willReturn(true);

        $cohorts = [
            ['listId' => 30, 'onlySubscribers' => false, 'storeIds' => [1], 'includeUnassigned' => true],
            ['listId' => 40, 'onlySubscribers' => false, 'storeIds' => [2], 'includeUnassigned' => false],
        ];
        $cohortResolver = $this->createStub(ListCohortResolver::class);
        $cohortResolver->method('resolveGuests')->willReturn($cohorts);
        $registrationStoreResolver = $this->createStub(RegistrationStoreResolver::class);
        $registrationStoreResolver->method('getWebsiteStoreIds')->willReturn([1, 2]);

        $queriedStores = [];
        $collection = $this->createMock(ContactCollection::class);
        $collection->method('getGuestsByStores')->willReturnCallback(
            function ($websiteId, $storeIds) use (&$queriedStores) {
                $queriedStores[] = $storeIds;
                $result = $this->createStub(ContactCollection::class);
                // Each cohort returns one page, then nothing.
                $result->method('getItems')->willReturn(
                    count($queriedStores) % 2 === 1 ? [new \Magento\Framework\DataObject(['id' => 1])] : []
                );
                return $result;
            }
        );
        $collectionFactory = $this->createStub(ContactCollectionFactory::class);
        $collectionFactory->method('create')->willReturn($collection);

        $exportedListIds = [];
        $exporter = $this->createStub(GuestExporter::class);
        $exporter->method('export')->willReturnCallback(
            function ($guests, $website, $listId) use (&$exportedListIds) {
                $exportedListIds[] = $listId;
                return [$listId => 'contact'];
            }
        );
        $exporterFactory = $this->createStub(GuestExporterFactory::class);
        $exporterFactory->method('create')->willReturn($exporter);

        $mergeManager = $this->createMock(MergeManager::class);
        $mergeManager->method('mergeBatch')->willReturnCallback(
            static fn ($batch, $megaBatch) => $megaBatch + $batch
        );

        $processor = $this->createMock(MegaBatchProcessor::class);
        $processedBatches = [];
        $processor->method('process')->willReturnCallback(
            function ($batch, $websiteId, $type) use (&$processedBatches) {
                $processedBatches[] = [$batch, $type];
            }
        );
        $processorFactory = $this->createStub(MegaBatchProcessorFactory::class);
        $processorFactory->method('create')->willReturn($processor);

        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn(100);

        $sync = new Guest(
            $collectionFactory,
            $helper,
            $scopeConfig,
            $storeManager,
            $this->createStub(Logger::class),
            $processorFactory,
            $mergeManager,
            $exporterFactory,
            $cohortResolver,
            $registrationStoreResolver
        );

        $sync->sync();

        $this->assertSame([[1], [1], [2], [2]], $queriedStores);
        $this->assertSame([30, 40], $exportedListIds);
        $this->assertCount(1, $processedBatches);
        $this->assertSame([30 => 'contact', 40 => 'contact'], $processedBatches[0][0]);
        $this->assertSame(Importer::IMPORT_TYPE_GUEST, $processedBatches[0][1]);
    }
}
