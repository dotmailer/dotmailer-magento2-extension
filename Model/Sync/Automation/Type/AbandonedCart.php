<?php

namespace Dotdigitalgroup\Email\Model\Sync\Automation\Type;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Contact\ContactResponseHandler;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation as AutomationResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory;
use Dotdigitalgroup\Email\Model\Sales\QuoteFactory as DotdigitalQuoteFactory;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationProcessor;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldUpdateHandler;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\Updater\AbandonedCartFactory as AbandonedCartUpdaterFactory;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Quote\Model\QuoteFactory;

class AbandonedCart extends AutomationProcessor
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var AbandonedCartUpdaterFactory
     */
    private $dataFieldUpdaterFactory;

    /**
     * @var DotdigitalQuoteFactory
     */
    private $ddgQuoteFactory;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var array
     */
    private $quoteItems;

    /**
     * AbandonedCart constructor.
     *
     * @param Data $helper
     * @param Logger $logger
     * @param AutomationResource $automationResource
     * @param CollectionFactory $contactCollectionFactory
     * @param DataFieldUpdateHandler $dataFieldUpdateHandler
     * @param ContactResponseHandler $contactResponseHandler
     * @param SubscriberFactory $subscriberFactory
     * @param AbandonedCartUpdaterFactory $dataFieldUpdaterFactory
     * @param DotdigitalQuoteFactory $ddgQuoteFactory
     * @param QuoteFactory $quoteFactory
     */
    public function __construct(
        Data $helper,
        Logger $logger,
        AutomationResource $automationResource,
        CollectionFactory $contactCollectionFactory,
        DataFieldUpdateHandler $dataFieldUpdateHandler,
        ContactResponseHandler $contactResponseHandler,
        SubscriberFactory $subscriberFactory,
        AbandonedCartUpdaterFactory $dataFieldUpdaterFactory,
        DotdigitalQuoteFactory $ddgQuoteFactory,
        QuoteFactory $quoteFactory
    ) {
        $this->logger = $logger;
        $this->dataFieldUpdaterFactory = $dataFieldUpdaterFactory;
        $this->ddgQuoteFactory = $ddgQuoteFactory;
        $this->quoteFactory = $quoteFactory;

        parent::__construct(
            $helper,
            $contactResponseHandler,
            $automationResource,
            $contactCollectionFactory,
            $dataFieldUpdateHandler,
            $subscriberFactory
        );
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

        $this->dataFieldUpdaterFactory->create()
            ->setDataFields(
                $email,
                $websiteId,
                $automation->getTypeId(),
                $automation->getStoreName(),
                $this->getNominatedAbandonedCartItem($quoteItems)
            )
            ->updateDataFields();
    }

    /**
     * @param string|int $quoteId
     * @return \Magento\Quote\Model\Quote\Item[]
     */
    private function getQuoteItems($quoteId)
    {
        if (isset($this->quoteItems[$quoteId])) {
            return $this->quoteItems[$quoteId];
        }
        $quoteModel = $this->quoteFactory->create()
            ->loadByIdWithoutStore($quoteId);

        try {
            $this->quoteItems[$quoteId] = $quoteModel->getAllItems();
        } catch (\Exception $e) {
            $this->quoteItems[$quoteId] = [];
            $this->logger->debug(
                sprintf('Error fetching items for quote ID: %s', $quoteId),
                [(string) $e]
            );
        }

        return $this->quoteItems[$quoteId];
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
