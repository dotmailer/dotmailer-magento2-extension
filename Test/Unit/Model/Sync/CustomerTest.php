<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Customer\RegistrationStoreResolver;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection as ContactCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\Batch\MegaBatchProcessor;
use Dotdigitalgroup\Email\Model\Sync\Batch\MegaBatchProcessorFactory;
use Dotdigitalgroup\Email\Model\Sync\Batch\MergeManager;
use Dotdigitalgroup\Email\Model\Sync\Customer;
use Dotdigitalgroup\Email\Model\Sync\Customer\Exporter;
use Dotdigitalgroup\Email\Model\Sync\Customer\ExporterFactory;
use Dotdigitalgroup\Email\Model\Sync\Customer\ListCohortResolver;
use Dotdigitalgroup\Email\Test\Unit\Traits\MockBuilderCompatibilityTrait;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class CustomerTest extends TestCase
{
    use MockBuilderCompatibilityTrait;

    /**
     * @var Data|\PHPUnit\Framework\MockObject\MockObject
     */
    private $helperMock;

    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var ContactCollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactCollectionFactoryMock;

    /**
     * @var MegaBatchProcessorFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $batchProcessorFactoryMock;

    /**
     * @var MergeManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mergeManagerMock;

    /**
     * @var Exporter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $exporterMock;

    /**
     * @var ExporterFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $exporterFactoryMock;

    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfigMock;

    /**
     * @var StoreManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $storeManagerMock;

    /**
     * @var WebsiteInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteInterfaceMock;

    /**
     * @var ListCohortResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    private $listCohortResolverMock;

    /**
     * @var RegistrationStoreResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registrationStoreResolverMock;

    /**
     * @var Customer
     */
    private $customer;

    /**
     * @var ContactCollection|ContactCollection&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactCollectionMock;

    protected function setUp(): void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->contactCollectionMock = $this->createMock(ContactCollection::class);
        $this->contactCollectionFactoryMock = $this->createMock(ContactCollectionFactory::class);
        $this->batchProcessorFactoryMock = $this->createMock(MegaBatchProcessorFactory::class);
        $this->mergeManagerMock = $this->createMock(MergeManager::class);
        $this->exporterFactoryMock = $this->createMock(ExporterFactory::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->listCohortResolverMock = $this->createMock(ListCohortResolver::class);
        $this->registrationStoreResolverMock = $this->createMock(RegistrationStoreResolver::class);
        $this->registrationStoreResolverMock->method('getWebsiteStoreIds')->willReturn([1]);

        $this->exporterMock = $this->createMock(Exporter::class);
        $this->websiteInterfaceMock = $this->getMockBuilder($this->classWithAddedMethods(
            WebsiteInterface::class,
            ['getStoreIds']
        ))
            ->onlyMethods(
                [
                'getId',
                'setId',
                'getCode',
                'setCode',
                'getName',
                'setName',
                'getDefaultGroupId',
                'setDefaultGroupId',
                'getExtensionAttributes',
                'setExtensionAttributes',
                'getStoreIds'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteInterfaceMock
            ->method('getId')
            ->willReturn(1);

        $this->customer = new Customer(
            $this->helperMock,
            $this->loggerMock,
            $this->contactCollectionFactoryMock,
            $this->batchProcessorFactoryMock,
            $this->mergeManagerMock,
            $this->exporterFactoryMock,
            $this->scopeConfigMock,
            $this->storeManagerMock,
            $this->listCohortResolverMock,
            $this->registrationStoreResolverMock
        );
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testNoExportingOrBatchingIfSyncIsNotConfigured()
    {
        $this->storeManagerMock->expects($this->once())
            ->method('getWebsites')
            ->willReturn([$this->websiteInterfaceMock]);

        $this->contactCollectionFactoryMock->expects($this->never())
            ->method('create');

        $this->contactCollectionMock->expects($this->never())
            ->method('getCustomersToImportByRegistrationStores');

        $this->exporterFactoryMock->expects($this->never())
            ->method('create');

        $this->batchProcessorFactoryMock->expects($this->never())
            ->method('create');

        $this->customer->sync();
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testNoExportingIfNoCustomersNeedSyncing()
    {
        $this->setupForOneEnabledWebsite();
        $this->setupFieldMapping();

        $this->contactCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->once())
            ->method('getCustomersToImportByRegistrationStores')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->once())
            ->method('getColumnValues')
            ->willReturn([]);

        $this->exporterMock->expects($this->never())
            ->method('export');

        $batchProcessorMock = $this->createMock(MegaBatchProcessor::class);
        $this->batchProcessorFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($batchProcessorMock);

        $this->customer->sync();
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testExportingStopsIfBreakValueIsExceeded()
    {
        $megaBatchSize = 10;
        $limit = 1;
        $breakValue = 1;

        $this->setupForOneEnabledWebsite();
        $this->setupFieldMapping();

        /* Set loop limits */
        $this->scopeConfigMock->expects($this->exactly(3))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls($megaBatchSize, $breakValue, $limit);

        $this->contactCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->once())
            ->method('getCustomersToImportByRegistrationStores')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->once())
            ->method('getColumnValues')
            ->willReturn([1, 21, 309]);

        $this->exporterMock->expects($this->once())
            ->method('export')
            ->willReturn($this->getCustomersBatch());

        $this->mergeManagerMock->expects($this->once())
            ->method('mergeBatch')
            ->willReturn($this->getCustomersBatch());

        $batchProcessorMock = $this->createMock(MegaBatchProcessor::class);
        $this->batchProcessorFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($batchProcessorMock);

        $data = $this->customer->sync();

        $this->assertEquals(3, $data['syncedCustomers']);
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testBatchIsProcessedOnceMegaBatchSizeIsExceeded()
    {
        $megaBatchSize = 5;
        $limit = 5;
        $breakValue = null;

        $this->setupForOneEnabledWebsite();
        $this->setupFieldMapping();

        /* Set loop limits */
        $this->scopeConfigMock->expects($this->exactly(3))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls($megaBatchSize, $breakValue, $limit);

        /* Two loops then none remaining */
        $this->contactCollectionFactoryMock->expects($this->exactly(3))
            ->method('create')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->exactly(3))
            ->method('getCustomersToImportByRegistrationStores')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->exactly(3))
            ->method('getColumnValues')
            ->willReturnOnConsecutiveCalls(
                [1, 21, 309],
                [321, 322, 323],
                []
            );

        $this->exporterMock->expects($this->exactly(2))
            ->method('export')
            ->willReturnOnConsecutiveCalls(
                $this->getCustomersBatch(),
                $this->getCustomersBatchTwo()
            );

        $this->mergeManagerMock->expects($this->exactly(2))
            ->method('mergeBatch')
            ->willReturnOnConsecutiveCalls(
                $this->getCustomersBatch(),
                $this->getMergedMegaBatch()
            );

        $batchProcessorMock = $this->createMock(MegaBatchProcessor::class);
        $this->batchProcessorFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($batchProcessorMock);

        $batchProcessorMock->expects($this->exactly(2))
            ->method('process');

        $data = $this->customer->sync();

        $this->assertEquals(6, $data['syncedCustomers']);
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException|\PHPUnit\Framework\MockObject\Exception
     */
    public function testMergeManagerNotCalledIfNothingToBatch()
    {
        $this->setupForOneEnabledWebsite();
        $this->setupFieldMapping();

        $this->contactCollectionFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->exactly(2))
            ->method('getCustomersToImportByRegistrationStores')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->exactly(2))
            ->method('getColumnValues')
            ->willReturnOnConsecutiveCalls(
                [1, 2, 3, 4, 5],
                []
            );

        $this->exporterMock->expects($this->once())
            ->method('export')
            ->willReturn([]);

        $this->mergeManagerMock->expects($this->never())
            ->method('mergeBatch');

        $batchProcessorMock = $this->createMock(MegaBatchProcessor::class);
        $this->batchProcessorFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($batchProcessorMock);

        $this->customer->sync();
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testCustomersAreExportedAndBatchedForTwoWebsites()
    {
        $megaBatchSize = 10;
        $limit = 5;
        $breakValue = null;

        $this->setupForTwoEnabledWebsites();

        /* Set loop limits */
        $this->scopeConfigMock->expects($this->exactly(4))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls(
                $megaBatchSize,
                $breakValue,
                $limit,
                $limit
            );

        $this->exporterFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturn($this->exporterMock);

        $this->exporterMock->expects($this->exactly(2))
            ->method('setFieldMapping');

        /* Two loops for each website (one with some customers, then none remaining) */
        $this->contactCollectionFactoryMock->expects($this->exactly(4))
            ->method('create')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->exactly(4))
            ->method('getCustomersToImportByRegistrationStores')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->exactly(4))
            ->method('getColumnValues')
            ->willReturnOnConsecutiveCalls(
                [1, 2, 3],
                [],
                [6, 7, 8],
                []
            );

        $this->exporterMock->expects($this->exactly(2))
            ->method('export')
            ->willReturnOnConsecutiveCalls(
                $this->getCustomersBatch(),
                $this->getCustomersBatchTwo()
            );

        $this->mergeManagerMock->expects($this->exactly(2))
            ->method('mergeBatch')
            ->willReturnOnConsecutiveCalls(
                $this->getCustomersBatch(),
                $this->getMergedMegaBatch()
            );

        $batchProcessorMock = $this->createMock(MegaBatchProcessor::class);
        $this->batchProcessorFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($batchProcessorMock);

        $batchProcessorMock->expects($this->exactly(2))
            ->method('process');

        $data = $this->customer->sync();

        $this->assertEquals(6, $data['syncedCustomers']);
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException|\PHPUnit\Framework\MockObject\Exception
     */
    public function testCohortsOfOneWebsiteShareASingleImportBatch()
    {
        $this->storeManagerMock->expects($this->once())
            ->method('getWebsites')
            ->willReturn([$this->websiteInterfaceMock]);
        $this->helperMock->method('isEnabled')->willReturn(true);
        $this->helperMock->method('isCustomerSyncEnabled')->willReturn(true);

        $secondCohort = array_merge($this->getCohort(), ['listId' => 456, 'storeIds' => [2]]);
        $this->listCohortResolverMock->expects($this->once())
            ->method('resolve')
            ->willReturn([$this->getCohort(), $secondCohort]);

        $this->exporterFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturn($this->exporterMock);

        $this->scopeConfigMock->method('getValue')
            ->willReturnOnConsecutiveCalls(1000, 0, 5, 5);

        $this->contactCollectionFactoryMock->method('create')->willReturn($this->contactCollectionMock);
        $this->contactCollectionMock->method('getCustomersToImportByRegistrationStores')
            ->willReturn($this->contactCollectionMock);
        $this->contactCollectionMock->method('getColumnValues')
            ->willReturnOnConsecutiveCalls([1, 21, 309], [], [321, 322, 323], []);

        $listIds = [];
        $this->exporterMock->expects($this->exactly(2))
            ->method('export')
            ->willReturnCallback(function ($ids, $website, $listId) use (&$listIds) {
                $listIds[] = $listId;
                return $listId === 123 ? $this->getCustomersBatch() : $this->getCustomersBatchTwo();
            });

        $this->mergeManagerMock->method('mergeBatch')
            ->willReturnOnConsecutiveCalls($this->getCustomersBatch(), $this->getMergedMegaBatch());

        $batchProcessorMock = $this->createMock(MegaBatchProcessor::class);
        $this->batchProcessorFactoryMock->method('create')->willReturn($batchProcessorMock);
        $batchProcessorMock->expects($this->once())->method('process');

        $data = $this->customer->sync();

        $this->assertSame([123, 456], $listIds);
        $this->assertEquals(6, $data['syncedCustomers']);
    }

    /**
     * Reflects the edge-case where we have duplicate contacts in our table.
     *
     * The customerIdCount is 10 and we use this for the offset on the next query
     * BUT the mega batch size is only 3 (i.e. 3 distinct ids) therefore the total
     * synced is also only 3.
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testCustomersCountIfDuplicateIdsInTable()
    {
        $megaBatchSize = 10;
        $limit = 10;
        $breakValue = null;

        $this->setupForOneEnabledWebsite();
        $this->setupFieldMapping();

        /* Set loop limits */
        $this->scopeConfigMock->expects($this->exactly(3))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls($megaBatchSize, $breakValue, $limit);

        $this->contactCollectionFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->exactly(2))
            ->method('getCustomersToImportByRegistrationStores')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->exactly(2))
            ->method('getColumnValues')
            ->willReturnOnConsecutiveCalls(
                [1, 1, 1, 21, 21, 21, 21, 309, 309, 309],
                []
            );

        $this->exporterMock->expects($this->once())
            ->method('export')
            ->willReturn($this->getCustomersBatch());

        $this->mergeManagerMock->expects($this->once())
            ->method('mergeBatch')
            ->willReturn($this->getCustomersBatch());

        $batchProcessorMock = $this->createMock(MegaBatchProcessor::class);
        $this->batchProcessorFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($batchProcessorMock);

        $data = $this->customer->sync();

        $this->assertEquals(3, $data['syncedCustomers']);
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException|\PHPUnit\Framework\MockObject\Exception
     */
    public function testEmptyBatchContinuesToSync()
    {
        $megaBatchSize = 10;
        $limit = 10;
        $breakValue = null;

        $this->setupForOneEnabledWebsite();
        $this->setupFieldMapping();

        $this->scopeConfigMock->expects($this->exactly(3))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls($megaBatchSize, $breakValue, $limit);

        $this->contactCollectionFactoryMock->expects($this->exactly(3))
            ->method('create')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->exactly(3))
            ->method('getCustomersToImportByRegistrationStores')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->exactly(3))
            ->method('getColumnValues')
            ->willReturnOnConsecutiveCalls(
                [1, 2, 3, 4, 5],
                [6, 7, 8, 9, 10],
                []
            );

        $this->exporterMock->expects($this->exactly(2))
            ->method('export')
            ->willReturnOnConsecutiveCalls(
                [],
                $this->getCustomersBatchTwo()
            );

        $this->mergeManagerMock->expects($this->once())
            ->method('mergeBatch')
            ->willReturn($this->getCustomersBatchTwo());

        $batchProcessorMock = $this->createMock(MegaBatchProcessor::class);
        $this->batchProcessorFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($batchProcessorMock);

        $this->customer->sync();
    }

    /**
     * @return void
     */
    private function setupForOneEnabledWebsite()
    {
        $this->storeManagerMock->expects($this->once())
            ->method('getWebsites')
            ->willReturn([$this->websiteInterfaceMock]);

        $this->helperMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->helperMock->expects($this->once())
            ->method('isCustomerSyncEnabled')
            ->willReturn(true);

        $this->listCohortResolverMock->expects($this->once())
            ->method('resolve')
            ->willReturn([$this->getCohort()]);
    }

    /**
     * @return void
     */
    private function setupForTwoEnabledWebsites()
    {
        $this->storeManagerMock->expects($this->once())
            ->method('getWebsites')
            ->willReturn(
                [
                $this->websiteInterfaceMock,
                $this->websiteInterfaceMock
                ]
            );

        $this->helperMock->expects($this->exactly(2))
            ->method('isEnabled')
            ->willReturn(true);

        $this->helperMock->expects($this->exactly(2))
            ->method('isCustomerSyncEnabled')
            ->willReturn(true);

        $this->listCohortResolverMock->expects($this->exactly(2))
            ->method('resolve')
            ->willReturn([$this->getCohort()]);
    }

    /**
     * @return array
     */
    private function getCohort()
    {
        return [
            'listId' => 123,
            'onlySubscribers' => true,
            'storeIds' => [1],
            'includeUnassigned' => true
        ];
    }

    /**
     * @return void
     */
    private function setupFieldMapping()
    {
        $this->exporterFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->exporterMock);

        $this->exporterMock->expects($this->once())
            ->method('setFieldMapping');
    }

    /**
     * Some customers.
     *
     * @return array[]
     */
    private function getCustomersBatch()
    {
        return [
            1 => ['chazco@emailsim.io', 'Html', '1', 0, null, 0.0, 'Chaz', 'Kangaroo'],
            21 => ['chaz2@emailsim.io', 'Html', '1', 0, null, 0.0, 'Dave', 'Dot'],
            309 => ['chaz3@emailsim.io', 'Html', '1', 0, null, 0.0, 'Chip', 'Chop'],
        ];
    }

    /**
     * Some more customers.
     *
     * @return array[]
     */
    private function getCustomersBatchTwo()
    {
        return [
            321 => ['chazco@emailsim.io', 'Html', '1', 0, null, 0.0, 'Chaz', 'Kangaroo'],
            322 => ['chaz2@emailsim.io', 'Html', '1', 0, null, 0.0, 'Dave', 'Dot'],
            323 => ['chaz3@emailsim.io', 'Html', '1', 0, null, 0.0, 'Chip', 'Chop'],
        ];
    }

    /**
     * Some more customers.
     *
     * @return array[]
     */
    private function getMergedMegaBatch()
    {
        return [
            1 => ['chazco@emailsim.io', 'Html', '1', 0, null, 0.0, 'Chaz', 'Kangaroo'],
            21 => ['chaz2@emailsim.io', 'Html', '1', 0, null, 0.0, 'Dave', 'Dot'],
            309 => ['chaz3@emailsim.io', 'Html', '1', 0, null, 0.0, 'Chip', 'Chop'],
            321 => ['chazco@emailsim.io', 'Html', '1', 0, null, 0.0, 'Chaz', 'Kangaroo'],
            322 => ['chaz2@emailsim.io', 'Html', '1', 0, null, 0.0, 'Dave', 'Dot'],
            323 => ['chaz3@emailsim.io', 'Html', '1', 0, null, 0.0, 'Chip', 'Chop'],
        ];
    }
}
