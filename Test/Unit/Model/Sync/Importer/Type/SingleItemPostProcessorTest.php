<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Importer\Type;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer as ImporterResource;
use Dotdigitalgroup\Email\Model\Sync\Importer\ImporterCurlErrorChecker;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\Contact\Update;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\SingleItemPostProcessor;
use Magento\Framework\Stdlib\DateTime;
use PHPUnit\Framework\TestCase;

class SingleItemPostProcessorTest extends TestCase
{
    /**
     * @var Data|\PHPUnit\Framework\MockObject\MockObject
     */
    private $helperMock;

    /**
     * @var ImporterModel|\PHPUnit\Framework\MockObject\MockObject
     */
    private $importerModelMock;

    /**
     * @var ImporterResource|\PHPUnit\Framework\MockObject\MockObject
     */
    private $importerResourceMock;

    /**
     * @var ImporterCurlErrorChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    private $curlErrorCheckerMock;

    /**
     * @var DateTime|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dateTimeMock;

    /**
     * @var SingleItemPostProcessor
     */
    private $singleItemPostProcessor;

    protected function setUp() :void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->importerModelMock = $this->getMockBuilder(ImporterModel::class)
            ->addMethods(['setImportStatus', 'setImportFinished', 'setImportStarted', 'setMessage'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->importerResourceMock = $this->createMock(ImporterResource::class);
        $this->curlErrorCheckerMock = $this->createMock(ImporterCurlErrorChecker::class);
        $this->dateTimeMock = $this->createMock(DateTime::class);

        $this->singleItemPostProcessor = new SingleItemPostProcessor(
            $this->helperMock,
            $this->importerResourceMock,
            $this->curlErrorCheckerMock,
            $this->dateTimeMock,
            []
        );
    }

    /**
     * testHandleItemAfterSyncSuccessful
     *
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function testHandleItemAfterSyncSuccessful()
    {
        $item = $this->importerModelMock;
        $result = new \stdClass();
        $result->id = 12345;

        $item->expects($this->once())
            ->method('setImportStatus')
            ->with(ImporterModel::IMPORTED)
            ->willReturn($item);

        $item->expects($this->once())
            ->method('setImportFinished')
            ->willReturn($item);

        $item->expects($this->once())
            ->method('setImportStarted')
            ->willReturn($item);

        $item->expects($this->once())
            ->method('setMessage')
            ->willReturn($item);

        $this->singleItemPostProcessor->handleItemAfterSync($item, $result);
    }

    /**
     * testHandleItemAfterSyncWithMessage
     *
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function testHandleItemAfterSyncWithMessage()
    {
        $item = $this->importerModelMock;
        $result = new \stdClass();
        $result->message = 'Error message from API';

        $item->expects($this->once())
            ->method('setImportStatus')
            ->with(ImporterModel::FAILED)
            ->willReturn($item);

        $item->expects($this->once())
            ->method('setMessage')
            ->willReturn($item);

        $item->expects($this->never())->method('setImportFinished');
        $item->expects($this->never())->method('setImportStarted');

        $this->singleItemPostProcessor->handleItemAfterSync($item, $result);
    }

    /**
     * testHandleItemAfterSyncWithApiMessage
     *
     * Subscriber_Resubscribed returns:
     * a) a result containing the contact model
     * b)
     *
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function testHandleItemAfterSyncWithApiMessage()
    {
        $item = $this->importerModelMock;
        $result = new \stdClass();
        $result->id = 12345;
        $apiMessage = Update::ERROR_CONTACT_ALREADY_SUBSCRIBED;

        $item->expects($this->once())
            ->method('setImportStatus')
            ->with(ImporterModel::IMPORTED)
            ->willReturn($item);

        $item->expects($this->once())
            ->method('setImportFinished')
            ->willReturn($item);

        $item->expects($this->once())
            ->method('setImportStarted')
            ->willReturn($item);

        $item->expects($this->once())
            ->method('setMessage')
            ->with($apiMessage)
            ->willReturn($item);

        $this->singleItemPostProcessor->handleItemAfterSync($item, $result, $apiMessage);
    }

    /**
     * testHandleItemAfterSyncWhenResultIsNull
     *
     * Single_Delete and Contact_Delete imports return a null result (204 No Content).
     * These should still be marked as imported.
     *
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function testHandleItemAfterSyncWhenResultIsNull()
    {
        $item = $this->importerModelMock;
        $result = null;

        $item->expects($this->once())
            ->method('setImportStatus')
            ->with(ImporterModel::IMPORTED)
            ->willReturn($item);

        $item->expects($this->once())
            ->method('setImportFinished')
            ->willReturn($item);

        $item->expects($this->once())
            ->method('setImportStarted')
            ->willReturn($item);

        $item->expects($this->once())
            ->method('setMessage')
            ->willReturn($item);

        $this->singleItemPostProcessor->handleItemAfterSync($item, $result);
    }
}
