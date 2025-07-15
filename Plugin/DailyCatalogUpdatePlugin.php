<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Plugin;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Magento\CatalogRule\Cron\DailyCatalogUpdate;
use Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;

class DailyCatalogUpdatePlugin
{
    /**
     * @var CollectionFactory
     */
    private $ruleCollectionFactory;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var Data
     */
    private $emailHelper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param CollectionFactory $ruleCollectionFactory
     * @param ProductMetadataInterface $productMetadata
     * @param ManagerInterface $eventManager
     * @param Data $emailHelper
     * @param Logger $logger
     * @param DateTime $dateTime
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CollectionFactory $ruleCollectionFactory,
        ProductMetadataInterface $productMetadata,
        ManagerInterface $eventManager,
        Data $emailHelper,
        Logger $logger,
        DateTime $dateTime,
        StoreManagerInterface $storeManager
    ) {
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->productMetadata = $productMetadata;
        $this->eventManager = $eventManager;
        $this->emailHelper = $emailHelper;
        $this->logger = $logger;
        $this->dateTime = $dateTime;
        $this->storeManager = $storeManager;
    }

    /**
     * After executing the daily catalog update, check for expired rules
     *
     * @param DailyCatalogUpdate $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterExecute(DailyCatalogUpdate $subject, $result)
    {
        if (!$this->isPluginEnabled()) {
            return $result;
        }

        // Only execute in Community Edition
        if ($this->productMetadata->getEdition() !== 'Community') {
            return $result;
        }

        $this->debug('Checking for catalog rules with scheduled updates');

        try {
            $today = $this->dateTime->gmtDate('Y-m-d');
            $yesterday = $this->dateTime->gmtDate('Y-m-d', time() - 86400);
            // get rules that are active and from_date that is today or to_date that is yesterday
            $ruleCollection = $this->ruleCollectionFactory->create();
            $ruleCollection->addFieldToFilter('is_active', 1);
            $ruleCollection->addFieldToFilter(
                ['from_date', 'to_date', 'to_date'],
                [
                    ['eq' => $today],
                    ['eq' => $today],
                    ['eq' => $yesterday]
                ]
            );
            $this->debug($ruleCollection->getSize().' catalog rules found with scheduled updates', [$ruleCollection]);

            foreach ($ruleCollection as $rule) {
                $origData = $rule->getData();

                foreach ($origData as $key => $value) {
                    $rule->setOrigData($key, $value);
                }

                // Reset date field to ensure it is considered changed
                foreach ($origData as $key => $value) {
                    if (($key == 'to_date' || $key == 'from_date') && ($today === $value || $yesterday === $value)) {
                        $rule->setOrigData($key, null);
                        break;
                    }
                }

                // Dispatch event for the CatalogRuleObserver to handle
                $this->eventManager->dispatch(
                    'catalogrule_rule_daily_update',
                    ['rule' => $rule]
                );
            }
        } catch (\Exception $e) {
            $this->logger->error('Error checking for scheduled catalog rule updates', [$e->getMessage()]);
        }

        return $result;
    }

    /**
     * Check if plugin should be executed.
     *
     * @return bool
     */
    private function isPluginEnabled(): bool
    {
        foreach ($this->storeManager->getWebsites() as $website) {
            if ($this->emailHelper->isEnabled($website->getId()) &&
                $this->emailHelper->catalogIndexPricesEnabled($website->getId())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log debug messages if debugging is enabled
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    private function debug(string $message, array $context = []): void
    {
        if ($this->emailHelper->isDebugEnabled()) {
            $this->logger->debug('DailyCatalogUpdatePlugin: ' . $message, $context);
        }
    }
}
