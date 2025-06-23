<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Queue\Catalog;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Queue\Data\PriceRuleData;
use Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\CatalogRule\Model\RuleFactory;

class PriceRuleUpdateConsumer
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CatalogFactory
     */
    private $catalogResourceFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var RuleFactory
     */
    private $ruleFactory;

    /**
     * @param Logger $logger
     * @param CatalogFactory $catalogResourceFactory
     * @param SerializerInterface $serializer
     * @param RuleFactory $ruleFactory
     */
    public function __construct(
        Logger $logger,
        CatalogFactory $catalogResourceFactory,
        SerializerInterface $serializer,
        RuleFactory $ruleFactory
    ) {
        $this->logger = $logger;
        $this->catalogResourceFactory = $catalogResourceFactory;
        $this->serializer = $serializer;
        $this->ruleFactory = $ruleFactory;
    }

    /**
     * Gather and set products as unprocessed
     *
     * Matching products will be reset, including those previously affected when rule conditions change.
     *
     * @param PriceRuleData $priceRuleData
     * @return void
     */
    public function process(PriceRuleData $priceRuleData): void
    {
        $oldRule = $this->ruleFactory->create()->setData($this->serializer->unserialize($priceRuleData->getOldRule()));
        $newRule = $this->ruleFactory->create()->setData($this->serializer->unserialize($priceRuleData->getNewRule()));

        $oldRuleProductIds = array_keys($oldRule->getMatchingProductIds());
        $newRuleProductIds = array_keys($newRule->getMatchingProductIds());

        $productIds = $oldRuleProductIds;
        foreach ($newRuleProductIds as $productId) {
            if (!in_array($productId, $productIds)) {
                $productIds[] = $productId;
            }
        }
        $catalogResource = $this->catalogResourceFactory->create();
        $catalogResource->setUnprocessedByIds($productIds);
    }
}
