<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Catalog;

use Dotdigitalgroup\Email\Api\Model\Sync\SyncDeferralInterface;
use Dotdigitalgroup\Email\Helper\Data as EmailConfig;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Mview;
use Magento\Framework\Mview\View\ChangelogTableNotExistsException;
use Magento\Store\Model\StoreManagerInterface;
use Dotdigitalgroup\Email\Logger\Logger;

class CatalogSyncDeferralHandler implements SyncDeferralInterface
{
    /**
     * @var array
     */
    public const INDEXERS = [
        'catalog_product_price',
        'catalogrule_rule',
        'catalogrule_product'
    ];

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var EmailConfig
     */
    private $emailConfig;

    /**
     * @param IndexerRegistry $indexerRegistry
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     * @param EmailConfig $emailConfig
     */
    public function __construct(
        IndexerRegistry $indexerRegistry,
        StoreManagerInterface $storeManager,
        Logger $logger,
        EmailConfig $emailConfig
    ) {
        $this->indexerRegistry = $indexerRegistry;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->emailConfig = $emailConfig;
    }

    /**
     * @inheritDoc
     */
    public function shouldDeferSync(): bool
    {
        $indexPricesEnabled = false;
        foreach ($this->storeManager->getWebsites() as $website) {
            if ($this->emailConfig->catalogIndexPricesEnabled($website->getId())) {
                $indexPricesEnabled = true;
                break;
            }
        }

        if ($indexPricesEnabled) {
            foreach (self::INDEXERS as $indexer) {
                $indexer = $this->indexerRegistry->get($indexer);
                $indexerScheduledUpdateCount = $this->getPendingCount($indexer->getView());
                if ($indexer->isInvalid()) {
                    $this->logger->info('Catalog sync: Indexer is invalid, skipping sync.');
                    return true;
                }

                if ($indexerScheduledUpdateCount > 0) {
                    $this->logger->info('Catalog sync: Indexer is scheduled, skipping sync.');
                    return true;
                }

                if ($indexer->isWorking()) {
                    $this->logger->info('Catalog sync: Indexer is working, skipping sync.');
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get pending schedule count
     *
     * @param Mview\ViewInterface $view
     * @return int
     */
    private function getPendingCount(Mview\ViewInterface $view): int
    {
        $changelog = $view->getChangelog();

        try {
            $currentVersionId = $changelog->getVersion();
        } catch (ChangelogTableNotExistsException $e) {
            $this->logger->debug(
                'Changelog table does not exist. Changelog: ' . $changelog->getName(),
                ['exception' => $e]
            );
            return 0;
        }

        $state = $view->getState();

        return count($changelog->getList($state->getVersionId(), $currentVersionId));
    }
}
