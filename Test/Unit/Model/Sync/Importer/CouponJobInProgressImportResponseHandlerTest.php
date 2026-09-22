<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Importer;

use Dotdigital\V3\Models\Contact\Import;
use Dotdigital\V3\Resources\Contacts;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\Client;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\ReportBuilderInterface as CouponJobReportBuilderInterface;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer as ImporterResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection as ImporterCollection;
use Dotdigitalgroup\Email\Model\Sync\Importer\CouponJobInProgressImportResponseHandler;
use Dotdigitalgroup\Email\Model\Sync\Importer\Context\InProgressImportContext;
use Dotdigitalgroup\Email\Model\Sync\Importer\ImporterItemStatusManager;
use Dotdigitalgroup\Email\Model\Sync\Importer\V3ImportStatusChecker;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class CouponJobInProgressImportResponseHandlerTest extends TestCase
{
    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var ImporterResource|MockObject
     */
    private $importerResourceMock;

    /**
     * @var ClientFactory|MockObject
     */
    private $clientFactoryMock;

    /**
     * @var CouponJobReportBuilderInterface|MockObject
     */
    private $reportBuilderMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerMock;

    /**
     * @var Client|MockObject
     */
    private $clientMock;

    /**
     * @var Contacts|MockObject
     */
    private $contactsMock;

    /**
     * @var ImporterCollection|MockObject
     */
    private $collectionMock;

    /**
     * @var Importer|MockObject
     */
    private $itemMock;

    /**
     * Magic setter calls recorded against the importer row, keyed by method name.
     *
     * @var array<string, array>
     */
    private $itemCalls = [];

    /**
     * @var CouponJobInProgressImportResponseHandler
     */
    private $handler;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->importerResourceMock = $this->createMock(ImporterResource::class);
        $this->clientFactoryMock = $this->createMock(ClientFactory::class);
        $this->reportBuilderMock = $this->createMock(CouponJobReportBuilderInterface::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);

        $this->contactsMock = $this->createMock(Contacts::class);
        $this->clientMock = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->clientMock->contacts = $this->contactsMock;

        $this->collectionMock = $this->createMock(ImporterCollection::class);
        $this->itemMock = $this->createMock(Importer::class);

        $this->collectionMock->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->itemMock]));

        $this->clientFactoryMock->method('create')
            ->willReturn($this->clientMock);

        $this->configureItemMagicMethods();

        $this->handler = new CouponJobInProgressImportResponseHandler(
            $this->loggerMock,
            new V3ImportStatusChecker($this->clientFactoryMock, $this->loggerMock),
            new ImporterItemStatusManager($this->importerResourceMock),
            $this->reportBuilderMock,
            $this->serializerMock
        );
    }

    /**
     * When the import is still in progress, process() returns 1 and no report is built.
     */
    public function testInProgressImportReturnsOneAndDoesNotBuildReport(): void
    {
        $response = new Import([
            'status' => 'NotFinished',
            'importId' => 'imp-1',
        ]);

        $this->contactsMock->expects($this->once())
            ->method('getImportById')
            ->with('imp-1')
            ->willReturn($response);

        $this->importerResourceMock->expects($this->once())
            ->method('save')
            ->with($this->itemMock);

        $this->reportBuilderMock->expects($this->never())
            ->method('build');

        $context = $this->createCouponJobContext();

        $itemsCount = $this->handler->process($context, $this->collectionMock);

        $this->assertSame(1, $itemsCount);
    }

    /**
     * When the import is finished, process() returns 0, marks the item imported and
     * forwards the imported count and per-contact failures to the ReportBuilder.
     */
    public function testFinishedImportBuildsReportWithImportedCountAndFailures(): void
    {
        $response = new Import([
            'status' => 'Finished',
            'importId' => 'imp-1',
            'summary' => ['updatedContacts' => 7],
            'failures' => [
                [
                    'identifiers' => ['email' => 'fail@example.com'],
                    'failures' => [
                        ['failureCode' => 'INVALID', 'description' => 'Bad address'],
                    ],
                ],
            ],
        ]);

        $this->contactsMock->expects($this->once())
            ->method('getImportById')
            ->with('imp-1')
            ->willReturn($response);

        $this->serializerMock->method('unserialize')
            ->willReturn(['coupon_job_id' => 55]);

        $this->importerResourceMock->expects($this->once())
            ->method('save')
            ->with($this->itemMock);

        $this->reportBuilderMock->expects($this->once())
            ->method('build')
            ->with(
                55,
                10,
                7,
                $this->callback(function (array $failures) {
                    return count($failures) === 1
                        && $failures[0]['email'] === 'fail@example.com'
                        && $failures[0]['failure_code'] === 'INVALID'
                        && $failures[0]['description'] === 'Bad address'
                        && isset($failures[0]['failed_at']);
                })
            );

        $context = $this->createCouponJobContext();

        $itemsCount = $this->handler->process($context, $this->collectionMock);

        $this->assertSame(0, $itemsCount);
    }

    /**
     * Contacts unexpectedly (re)created during the import are recorded as failures
     * with the "Updated deleted contact" failure code and forwarded to ReportBuilder.
     */
    public function testFinishedImportRecordsRecreatedContactsAsFailures(): void
    {
        $response = new Import([
            'status' => 'Finished',
            'importId' => 'imp-1',
            'summary' => ['updatedContacts' => 3],
            'created' => [
                ['identifiers' => ['email' => 'recreated@example.com']],
            ],
        ]);

        $this->contactsMock->expects($this->once())
            ->method('getImportById')
            ->with('imp-1')
            ->willReturn($response);

        $this->serializerMock->method('unserialize')
            ->willReturn(['coupon_job_id' => 55]);

        $this->importerResourceMock->expects($this->once())
            ->method('save')
            ->with($this->itemMock);

        $this->reportBuilderMock->expects($this->once())
            ->method('build')
            ->with(
                55,
                10,
                3,
                $this->callback(function (array $failures) {
                    return count($failures) === 1
                        && $failures[0]['email'] === 'recreated@example.com'
                        && $failures[0]['failure_code'] === 'Updated deleted contact'
                        && $failures[0]['description'] === 'Contact was previously deleted and has been'
                            . ' recreated by this import.'
                        && isset($failures[0]['failed_at']);
                })
            );

        $context = $this->createCouponJobContext();

        $itemsCount = $this->handler->process($context, $this->collectionMock);

        $this->assertSame(0, $itemsCount);
    }

    /**
     * When the status check throws, the item is marked FAILED and saved, and the
     * report is still reconciled via ReportBuilder so a failed final batch cannot
     * leave the coupon job stuck in PROCESSING.
     */
    public function testStatusCheckFailureStillReconcilesReport(): void
    {
        $this->contactsMock->expects($this->once())
            ->method('getImportById')
            ->with('imp-1')
            ->willThrowException(new \Exception('polling failed'));

        $this->serializerMock->method('unserialize')
            ->willReturn(['coupon_job_id' => 55]);

        $this->importerResourceMock->expects($this->once())
            ->method('save')
            ->with($this->itemMock);

        $this->reportBuilderMock->expects($this->once())
            ->method('build')
            ->with(55, 10, 0, []);

        $context = $this->createCouponJobContext();

        $itemsCount = $this->handler->process($context, $this->collectionMock);

        $this->assertSame(0, $itemsCount);
    }

    /**
     * A batch belonging to a cancelled coupon job is still polled to completion.
     *
     * The contacts were already accepted by Dotdigital, so abandoning the row would
     * under-report the batch and record counts the merchant sees on the cancelled
     * job. Polling therefore proceeds exactly as it would for a running job: the row
     * is marked IMPORTED and the real imported count is folded into the report.
     */
    public function testCancelledCouponJobStillPollsToCompletion(): void
    {
        $response = new Import([
            'status' => 'Finished',
            'importId' => 'imp-1',
            'summary' => ['updatedContacts' => 12],
        ]);

        $this->serializerMock->method('unserialize')
            ->willReturn(['coupon_job_id' => 55]);

        $this->contactsMock->expects($this->once())
            ->method('getImportById')
            ->with('imp-1')
            ->willReturn($response);

        $this->importerResourceMock->expects($this->once())
            ->method('save')
            ->with($this->itemMock);

        $this->reportBuilderMock->expects($this->once())
            ->method('build')
            ->with(55, 10, 12, []);

        $context = $this->createCouponJobContext();

        $this->assertSame(0, $this->handler->process($context, $this->collectionMock));

        $this->assertSame([Importer::IMPORTED], $this->itemCalls['setImportStatus'] ?? null);
    }

    /**
     * Configure the Importer mock's magic getters/setters and data accessors.
     *
     * @return void
     */
    private function configureItemMagicMethods(): void
    {
        $this->itemCalls = [];

        $this->itemMock->method('__call')
            ->willReturnCallback(function (string $method, array $args = []) {
                $this->itemCalls[$method] = $args;

                return match ($method) {
                    'getWebsiteId' => 1,
                    'getImportId' => 'imp-1',
                    'setImportStatus', 'setImportFinished', 'setMessage' => $this->itemMock,
                    default => null,
                };
            });

        $this->itemMock->method('getId')->willReturn(10);
        $this->itemMock->method('getData')
            ->willReturnCallback(function ($key = '') {
                return $key === 'import_data' ? 'serialized-import-data' : null;
            });
    }

    /**
     * @return InProgressImportContext
     */
    private function createCouponJobContext(): InProgressImportContext
    {
        return new InProgressImportContext(
            'coupon_job',
            Importer::MODE_BULK_JSON,
            [Importer::IMPORT_TYPE_COUPON_JOB],
            'getImportById',
            'contacts'
        );
    }
}
