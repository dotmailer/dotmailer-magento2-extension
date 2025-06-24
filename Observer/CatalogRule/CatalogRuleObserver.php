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
use Psr\Log\LoggerInterface;

/**
 * Observer for catalog rule save after event.
 */
class CatalogRuleObserver implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var PriceRuleDataFactory
     */
    protected $priceRuleDataFactory;

    /**
     * @var PublisherInterface
     */
    protected $publisher;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var Data
     */
    private $emailHelper;

    /**
     * @param LoggerInterface $logger
     * @param PriceRuleDataFactory $priceRuleDataFactory
     * @param PublisherInterface $publisher
     * @param SerializerInterface $serializer
     * @param Data $emailHelper
     */
    public function __construct(
        LoggerInterface      $logger,
        PriceRuleDataFactory $priceRuleDataFactory,
        PublisherInterface   $publisher,
        SerializerInterface  $serializer,
        Data                 $emailHelper
    ) {
        $this->logger = $logger;
        $this->priceRuleDataFactory = $priceRuleDataFactory;
        $this->publisher = $publisher;
        $this->serializer = $serializer;
        $this->emailHelper = $emailHelper;
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
            /** @var \Magento\CatalogRule\Model\Rule $rule */
            $rule = $observer->getEvent()->getRule();

            if (!$this->shouldProcessRule($rule)) {
                return;
            }

            $message = $this->priceRuleDataFactory->create();
            // OrigData will be the same as Data if the rule is deleted.
            // OrigData will be null if the rule is new.
            $message->setOldRule($this->serializer->serialize($rule->getOrigData()));
            $message->setNewRule($this->serializer->serialize($rule->getData()));

            $this->publisher->publish('ddg.catalog.rules', $message);

        } catch (\Exception $e) {
            $this->logger->error('Error fetching old rule or processing new rule', [(string) $e]);
        }
    }

    /**
     * Check if the rule is viable for processing.
     *
     * @param Rule $rule
     * @return bool
     */
    private function shouldProcessRule(Rule $rule): bool
    {
        $oldRuleData = $rule->getOrigData();
        $oldRuleActive = false;
        if ($oldRuleData) {
            $oldRuleActive = (bool)$oldRuleData['is_active'] ?? false;
        }

        if (!$rule->getIsActive() && !$oldRuleActive) {
            return false;
        }

        if (!$this->emailHelper->isEnabled()) {
            return false;
        }

        foreach ($rule->getWebsiteIds() as $websiteId) {
            if ($this->emailHelper->catalogIndexPricesEnabled($websiteId)) {
                return true;
            }
        }

        return false;
    }
}
