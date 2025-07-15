<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Catalog;

use Dotdigitalgroup\Email\Helper\Config as EmailConfig;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Sync\Catalog\CatalogSyncDeferralHandler;
use Hoa\Iterator\Mock;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Mview\ViewInterface;
use Magento\Framework\Mview\View\ChangelogInterface;
use Magento\Framework\Mview\View\StateInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CatalogSyncDeferralHandlerTest extends TestCase
{
    /**
     * @var IndexerRegistry|MockObject
     */
    private $indexerRegistryMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var IndexerInterface|MockObject
     */
    private $indexerMock;

    /**
     * @var ViewInterface|MockObject
     */
    private $viewMock;

    /**
     * @var ChangelogInterface|MockObject
     */
    private $changelogMock;

    /**
     * @var StateInterface|MockObject
     */
    private $stateMock;

    /**
     * @var WebsiteInterface|MockObject
     */
    private $websiteMock;

    /**
     * @var CatalogSyncDeferralHandler
     */
    private $handler;

    /**
     * @var Data|MockObject
     */
    private $emailConfigMock;

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        $this->indexerRegistryMock = $this->createMock(IndexerRegistry::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->emailConfigMock = $this->createMock(Data::class);
        $this->indexerMock = $this->createMock(IndexerInterface::class);
        $this->viewMock = $this->createMock(ViewInterface::class);
        $this->changelogMock = $this->createMock(ChangelogInterface::class);
        $this->stateMock = $this->createMock(StateInterface::class);
        $this->websiteMock = $this->createMock(WebsiteInterface::class);

        $this->indexerMock->method('getView')->willReturn($this->viewMock);
        $this->viewMock->method('getChangelog')->willReturn($this->changelogMock);
        $this->viewMock->method('getState')->willReturn($this->stateMock);

        $this->handler = new CatalogSyncDeferralHandler(
            $this->indexerRegistryMock,
            $this->storeManagerMock,
            $this->loggerMock,
            $this->emailConfigMock
        );
    }

    /**
     * Test that shouldDeferSync returns true when the indexer is invalid.
     *
     * @return void
     */
    public function testShouldDeferSyncWhenIndexerIsInvalid(): void
    {
        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(CatalogSyncDeferralHandler::INDEXERS[0])
            ->willReturn($this->indexerMock);
        $this->indexerMock->method('isInvalid')->willReturn(true);

        $this->viewMock->method('getChangelog')->willReturn($this->changelogMock);
        $this->viewMock->method('getState')->willReturn($this->stateMock);
        $this->stateMock->method('getVersionId')->willReturn(1);
        $this->changelogMock->method('getVersion')->willReturn(1);
        $this->changelogMock->method('getList')->with(1, 1)->willReturn([]);
        $this->storeManagerMock->method('getWebsites')->willReturn([$this->websiteMock]);
        $this->emailConfigMock->method('catalogIndexPricesEnabled')->willReturn(true);

        $result = $this->handler->shouldDeferSync();

        $this->assertTrue($result);
    }

    /**
     * Test that shouldDeferSync returns true when the indexer is working.
     *
     * @return void
     */
    public function testShouldDeferSyncWhenIndexerIsWorking(): void
    {
        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(CatalogSyncDeferralHandler::INDEXERS[0])
            ->willReturn($this->indexerMock);
        $this->indexerMock->method('isInvalid')->willReturn(false);
        $this->indexerMock->method('isWorking')->willReturn(true);
        $this->indexerMock->method('getView')->willReturn($this->viewMock);
        $this->viewMock->method('getChangelog')->willReturn($this->changelogMock);
        $this->viewMock->method('getState')->willReturn($this->stateMock);
        $this->stateMock->method('getVersionId')->willReturn(1);
        $this->changelogMock->method('getVersion')->willReturn(1);
        $this->changelogMock->method('getList')->with(1, 1)->willReturn([]);
        $this->emailConfigMock->method('catalogIndexPricesEnabled')->willReturn(true);
        $this->storeManagerMock->method('getWebsites')->willReturn([$this->websiteMock]);

        $result = $this->handler->shouldDeferSync();

        $this->assertTrue($result);
    }

    /**
     * Test that shouldDeferSync returns true when the indexer has scheduled updates.
     *
     * @return void
     */
    public function testShouldDeferSyncWhenIndexerHasScheduledUpdates(): void
    {
        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(CatalogSyncDeferralHandler::INDEXERS[0])
            ->willReturn($this->indexerMock);
        $this->indexerMock->method('isInvalid')->willReturn(false);
        $this->indexerMock->method('isWorking')->willReturn(false);
        $this->indexerMock->method('getView')->willReturn($this->viewMock);
        $this->viewMock->method('getChangelog')->willReturn($this->changelogMock);
        $this->viewMock->method('getState')->willReturn($this->stateMock);
        $this->stateMock->method('getVersionId')->willReturn(1);
        $this->changelogMock->method('getVersion')->willReturn(2);
        $this->changelogMock->method('getList')->with(1, 2)->willReturn([1, 2]);
        $this->storeManagerMock->method('getWebsites')->willReturn([$this->websiteMock]);
        $this->emailConfigMock->method('catalogIndexPricesEnabled')->willReturn(true);

        $result = $this->handler->shouldDeferSync();

        $this->assertTrue($result);
    }

    /**
     * Test that shouldDeferSync returns false when conditions are met.
     *
     * @return void
     */
    public function testShouldNotDeferSyncWhenIndexPricesDisabled(): void
    {
        $this->storeManagerMock->method('getWebsites')->willReturn([$this->websiteMock]);
        $this->emailConfigMock->method('catalogIndexPricesEnabled')->willReturn(false);

        $this->indexerRegistryMock->expects($this->never())
            ->method('get');

        $result = $this->handler->shouldDeferSync();

        $this->assertFalse($result);
    }
}
