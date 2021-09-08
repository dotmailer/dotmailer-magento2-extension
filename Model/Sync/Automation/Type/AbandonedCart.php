<?php

namespace Dotdigitalgroup\Email\Model\Sync\Automation\Type;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation as AutomationResource;
use Dotdigitalgroup\Email\Model\Sales\QuoteFactory as DotdigitalQuoteFactory;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationProcessor;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldUpdateHandler;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\Updater\AbandonedCart as AbandonedCartUpdater;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\QuoteFactory;

class AbandonedCart extends AutomationProcessor
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var AbandonedCartUpdater
     */
    private $dataFieldUpdater;

    /**
     * @var DotdigitalQuoteFactory
     */
    private $ddgQuoteFactory;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var array
     */
    private $quoteItems;

    /**
     * AbandonedCart constructor.
     * @param Data $helper
     * @param Logger $logger
     * @param AutomationResource $automationResource
     * @param DataFieldUpdateHandler $dataFieldUpdateHandler
     * @param AbandonedCartUpdater $dataFieldUpdater
     * @param DotdigitalQuoteFactory $ddgQuoteFactory
     * @param QuoteFactory $quoteFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Data $helper,
        Logger $logger,
        AutomationResource $automationResource,
        DataFieldUpdateHandler $dataFieldUpdateHandler,
        AbandonedCartUpdater $dataFieldUpdater,
        DotdigitalQuoteFactory $ddgQuoteFactory,
        QuoteFactory $quoteFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->logger = $logger;
        $this->dataFieldUpdater = $dataFieldUpdater;
        $this->ddgQuoteFactory = $ddgQuoteFactory;
        $this->quoteFactory = $quoteFactory;
        $this->scopeConfig = $scopeConfig;

        parent::__construct($helper, $automationResource, $dataFieldUpdateHandler);
    }

    /**
     * @param \Dotdigitalgroup\Email\Model\Automation $automation
     * @return bool
     */
    protected function shouldExitLoop($automation)
    {
        $quoteItems = $this->getQuoteItems($automation->getTypeId());

        if (count($quoteItems) === 0) {
            $this->automationResource->setStatusAndSaveAutomation(
                $automation,
                StatusInterface::CANCELLED
            );
            return true;
        }

        return false;
    }

    /**
     * @param \Dotdigitalgroup\Email\Model\Automation $automation
     * @param string $email
     * @param string|int $websiteId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function orchestrateDataFieldUpdate($automation, $email, $websiteId)
    {
        $quoteItems = $this->getQuoteItems($automation->getTypeId());

        $this->dataFieldUpdater->setDatafields(
            $email,
            $websiteId,
            $automation->getTypeId(),
            $automation->getStoreName(),
            $this->getNominatedAbandonedCartItem($quoteItems)
        )->updateDataFields();
    }

    /**
     * @param string|int $quoteId
     * @return \Magento\Quote\Model\Quote\Item[]
     */
    private function getQuoteItems($quoteId)
    {
        if (isset($this->quoteItems)) {
            return $this->quoteItems;
        }
        $quoteModel = $this->quoteFactory->create()
            ->loadByIdWithoutStore($quoteId);

        try {
            $this->quoteItems = $quoteModel->getAllItems();
        } catch (\Exception $e) {
            $this->quoteItems = [];
            $this->logger->debug(
                sprintf('Error fetching items for quote ID: %s', $quoteId),
                [(string) $e]
            );
        }

        return $this->quoteItems;
    }

    /**
     * Nominate the most expensive item in the cart as the 'abandoned product'
     *
     * @param \Magento\Quote\Model\Quote\Item[] $items
     * @return mixed
     */
    private function getNominatedAbandonedCartItem($items)
    {
        return $this->ddgQuoteFactory->create()
            ->getMostExpensiveItem($items);
    }
}
