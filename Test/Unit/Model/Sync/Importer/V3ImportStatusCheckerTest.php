<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Importer;

use Dotdigital\Exception\ResponseValidationException;
use Dotdigital\V3\Models\Contact\Import;
use Dotdigital\V3\Resources\Contacts;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\Client;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\Sync\Importer\Context\InProgressImportContext;
use Dotdigitalgroup\Email\Model\Sync\Importer\V3ImportStatusChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class V3ImportStatusCheckerTest extends TestCase
{
    /**
     * @var ClientFactory|MockObject
     */
    private $clientFactoryMock;

    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var Client|MockObject
     */
    private $clientMock;

    /**
     * @var Contacts|MockObject
     */
    private $contactsMock;

    /**
     * @var ImporterModel|MockObject
     */
    private $itemMock;

    /**
     * @var V3ImportStatusChecker
     */
    private $importStatusChecker;

    protected function setUp(): void
    {
        $this->clientFactoryMock = $this->createMock(ClientFactory::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->contactsMock = $this->createMock(Contacts::class);

        $this->clientMock = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->clientMock->contacts = $this->contactsMock;

        $this->itemMock = $this->createMock(ImporterModel::class);
        $this->itemMock->method('__call')
            ->willReturnCallback(function (string $method) {
                return match ($method) {
                    'getWebsiteId' => 1,
                    'getImportId' => 'imp-1',
                    default => null,
                };
            });

        $this->clientFactoryMock->method('create')
            ->willReturn($this->clientMock);

        $this->importStatusChecker = new V3ImportStatusChecker(
            $this->clientFactoryMock,
            $this->loggerMock
        );
    }

    public function testCheckReturnsTheImportFromTheConfiguredResourceAndMethod(): void
    {
        $response = new Import(['status' => 'Finished', 'importId' => 'imp-1']);

        $this->contactsMock->expects($this->once())
            ->method('getImportById')
            ->with('imp-1')
            ->willReturn($response);

        $result = $this->importStatusChecker->check(
            $this->itemMock,
            new InProgressImportContext('v3', ImporterModel::MODE_BULK_JSON, ['Consent'], 'getImportById', 'contacts')
        );

        $this->assertSame($response, $result);
    }

    public function testClientIsCreatedOncePerWebsite(): void
    {
        $this->clientFactoryMock = $this->createMock(ClientFactory::class);
        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->with(['data' => ['websiteId' => 1]])
            ->willReturn($this->clientMock);

        $importStatusChecker = new V3ImportStatusChecker(
            $this->clientFactoryMock,
            $this->loggerMock
        );

        $this->assertSame($this->clientMock, $importStatusChecker->getClient(1));
        $this->assertSame($this->clientMock, $importStatusChecker->getClient('1'));
    }

    public function testValidationExceptionIsLoggedWithPrefixAndRethrown(): void
    {
        $this->contactsMock->method('getImportById')
            ->willThrowException(new ResponseValidationException('Api is unhappy', 400));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('CouponJob Checking import id imp-1'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Api is unhappy');

        $this->importStatusChecker->check(
            $this->itemMock,
            new InProgressImportContext(
                'coupon_job',
                ImporterModel::MODE_BULK_JSON,
                ['CouponJob'],
                'getImportById',
                'contacts'
            ),
            'CouponJob'
        );
    }
}
