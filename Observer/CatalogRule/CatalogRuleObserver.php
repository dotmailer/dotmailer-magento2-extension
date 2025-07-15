<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Observer\CatalogRule;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Queue\Data\PriceRuleDataFactory;
use Magento\CatalogRule\Model\Rule;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Observer for catalog rule save after event.
 */
class CatalogRuleObserver implements ObserverInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var PriceRuleDataFactory
     */
    private $priceRuleDataFactory;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var Data
     */
    private $emailHelper;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var CollectionFactory
     */
    private $ruleCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Logger $logger
     * @param PriceRuleDataFactory $priceRuleDataFactory
     * @param PublisherInterface $publisher
     * @param SerializerInterface $serializer
     * @param Data $emailHelper
     * @param ProductMetadataInterface $productMetadata
     * @param CollectionFactory $ruleCollectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Logger $logger,
        PriceRuleDataFactory $priceRuleDataFactory,
        PublisherInterface $publisher,
        SerializerInterface $serializer,
        Data $emailHelper,
        ProductMetadataInterface $productMetadata,
        CollectionFactory $ruleCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->logger = $logger;
        $this->priceRuleDataFactory = $priceRuleDataFactory;
        $this->publisher = $publisher;
        $this->serializer = $serializer;
        $this->emailHelper = $emailHelper;
        $this->productMetadata = $productMetadata;
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->storeManager = $storeManager;
    }
    /**
     * Handles catalog rule change events.
     *
     * @param Observer $observer The event observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            if (!$this->isConfigEnabled()) {
                return;
            }

            $event = $observer->getEvent();
            $eventName = $event->getName();

            /** @var \Magento\CatalogRule\Model\Rule $rule */
            $rule = $event->getData('rule');

            $this->debug('Processing catalog rule update', [
                'event_name' => $eventName,
                'rule_id' => $rule->getId(),
                'rule_name' => $rule->getName()
            ]);

            $rule = $this->checkRuleForScheduledUpdates($rule, $eventName);
            if (!$this->shouldProcessRule($rule, $eventName)) {
                $this->debug('Rule is not valid for processing, skipping');
                return;
            }

            $message = $this->priceRuleDataFactory->create();
            $message->setOldRule($this->serializer->serialize($rule->getOrigData()));
            $message->setNewRule($this->serializer->serialize($rule->getData()));

            $this->publisher->publish('ddg.catalog.rules', $message);
            $this->debug('Rule message published');
        } catch (\Exception $e) {
            $this->logger->error('Error fetching old rule or processing new rule', [(string) $e]);
        }
    }

    /**
     * Check if the rule is viable for processing.
     *
     * @param Rule $rule
     * @param string $eventName
     * @return bool
     */
    private function shouldProcessRule(Rule $rule, string $eventName): bool
    {
        $oldRuleData = $rule->getOrigData() ?? [];
        $newRuleData = $rule->getData() ?? [];
        if ($this->productMetadata->getEdition() !== 'Community' && isset($newRuleData['staging'])) {
            $this->debug('Rule is staging');
            return false;
        }

        if ($eventName === 'catalogrule_rule_delete_after') {
            $this->debug('Rule deleted');
            if (!$oldRuleData['is_active']) {
                $this->debug('Deleted rule is not active');
                return false;
            }
        } elseif (!isset($oldRuleData['rule_id'])) {
            $this->debug('Rule created');
            if (!$newRuleData['is_active']) {
                $this->debug('New rule is not active');
                return false;
            }
        } elseif (!$this->ruleChanged($oldRuleData, $newRuleData)) {
            $this->debug('Rule has not changed');
            return false;
        }

        $websiteIds = array_unique(array_merge($oldRuleData['website_ids'] ?? [], $newRuleData['website_ids'] ?? []));
        foreach ($websiteIds as $websiteId) {
            if ($this->emailHelper->catalogIndexPricesEnabled($websiteId)) {
                return true;
            }
        }

        $this->debug('catalogIndexPrices config is not enabled, skipping processing');

        return false;
    }

    /**
     * Check if the rule is viable for processing.
     *
     * @param array $oldRuleData
     * @param array $newRuleData
     * @return bool
     */
    private function ruleChanged(array $oldRuleData, array $newRuleData): bool
    {
        $fieldsToCompare = [
            'rule_id', 'from_date', 'to_date', 'is_active',
            'conditions_serialized', 'actions_serialized', 'stop_rules_processing',
            'sort_order', 'simple_action', 'discount_amount', 'customer_group_ids', 'website_ids'
        ];

        foreach ($fieldsToCompare as $field) {
            $oldValue = $oldRuleData[$field] ?? null;
            $newValue = $newRuleData[$field] ?? null;
            if ($field === 'from_date' || $field === 'to_date') {
                if (isset($oldRuleData[$field])) {
                    if ($oldRuleData[$field] instanceof \DateTime) {
                        $oldValue = $oldRuleData[$field]->getTimestamp();
                    } else {
                        $oldValue= strtotime($oldRuleData[$field]);
                    }
                }
                if (isset($newRuleData[$field])) {
                    if ($newRuleData[$field] instanceof \DateTime) {
                        $newValue = $newRuleData[$field]->getTimestamp();
                    } else {
                        $newValue = strtotime($newRuleData[$field]);
                    }
                }
            }

            if ($oldValue !== $newValue) {
                $this->debug('Rule changed', ['field' => $field, 'old' => $oldValue, 'new' => $newValue]);
                return true;
            }
        }

        return false;
    }

    /**
     * Checks rule for scheduled updates and sets old rule data for scheduled updates.
     *
     * @param Rule $rule
     * @param string $eventName
     * @return Rule
     */
    private function checkRuleForScheduledUpdates(Rule $rule, string $eventName): Rule
    {
        try {
            if ($eventName === 'catalogrule_rule_delete_after') {
                $rule->setData([]);
                return $rule;
            }

            if ($this->productMetadata->getEdition() === 'Community') {
                return $rule;
            }

            $oldRuleData = $rule->getOrigData() ?? [];
            $newRuleData = $rule->getData() ?? [];

            // Skip scheduled update validation if the rule is staging
            if (isset($newRuleData['staging'])) {
                return $rule;
            }

            // Skip scheduled update validation if the rule is saved from the admin form
            if (isset($newRuleData['form_key'])) {
                return $rule;
            }

            // Scheduled updates will not have correct old rule data when the event is triggered
            // We need to fetch the previous rule data from the database
            if (!$this->ruleChanged($oldRuleData, $newRuleData)) {

                $ruleCollection = $this->ruleCollectionFactory->create();
                $ruleCollection->addFieldToFilter('rule_id', ['eq' => $newRuleData['rule_id']]);
                $ruleCollection->addFieldToFilter('row_id', ['lt' => $newRuleData['row_id']]);

                // This removes timestamp staging filters
                $ruleCollection->getSelect()->setPart('disable_staging_preview', true);
                $ruleCollection->setOrder('row_id', 'DESC')->setPageSize(1);

                $previousRule = $ruleCollection->getFirstItem();

                /** @var \Magento\CatalogRule\Model\Rule $previousRule */
                if ($previousRule->getRuleId()) {
                    $oldRuleData = $previousRule->getData();
                    $this->debug('Loading old rule data from scheduled updates history', [$oldRuleData]);
                    foreach ($oldRuleData as $key => $value) {
                        $rule->setOrigData($key, $value);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error checking rule for scheduled updates', [(string) $e]);
        }
        return $rule;
    }

    /**
     * Is config enabled.
     *
     * Check if both the connector and the catalog index prices switch config is enabled.
     *
     * @return bool
     */
    private function isConfigEnabled(): bool
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
     * Log debug messages if debugging is enabled.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    private function debug(string $message, array $context = []): void
    {
        if ($this->emailHelper->isDebugEnabled()) {
            $this->logger->debug('CatalogRuleObserver: '.$message, $context);
        }
    }
}
