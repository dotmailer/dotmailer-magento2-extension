<?php

namespace Dotdigitalgroup\Email\Model\Sync\Automation\Type;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Automation;
use Dotdigitalgroup\Email\Model\ContactFactory;
use Dotdigitalgroup\Email\Model\Contact\ContactResponseHandler;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation as AutomationResource;
use Dotdigitalgroup\Email\Model\Sales\QuoteFactory as DotdigitalQuoteFactory;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationProcessor;
use Dotdigitalgroup\Email\Model\Sync\Automation\ContactManager;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldCollector;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldTypeHandler;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\Updater\AbandonedCartFactory as AbandonedCartUpdaterFactory;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Quote\Model\QuoteFactory;

class AbandonedCart extends AutomationProcessor
{
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
     * @param ContactFactory $contactFactory
     * @param ContactManager $contactManager
     * @param DataFieldCollector $dataFieldCollector
     * @param DataFieldTypeHandler $dataFieldTypeHandler
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
        ContactFactory $contactFactory,
        ContactManager $contactManager,
        DataFieldCollector $dataFieldCollector,
        DataFieldTypeHandler $dataFieldTypeHandler,
        ContactResponseHandler $contactResponseHandler,
        SubscriberFactory $subscriberFactory,
        AbandonedCartUpdaterFactory $dataFieldUpdaterFactory,
        DotdigitalQuoteFactory $ddgQuoteFactory,
        QuoteFactory $quoteFactory
    ) {
        $this->dataFieldUpdaterFactory = $dataFieldUpdaterFactory;
        $this->ddgQuoteFactory = $ddgQuoteFactory;
        $this->quoteFactory = $quoteFactory;

        parent::__construct(
            $helper,
            $logger,
            $contactResponseHandler,
            $automationResource,
            $contactFactory,
            $contactManager,
            $dataFieldCollector,
            $dataFieldTypeHandler,
            $subscriberFactory
        );
    }

    /**
     * Check if automation should be processed.
     *
     * @param Automation $automation
     * @return bool
     */
    protected function shouldExitLoop(Automation $automation)
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
     * Retrieve automation data fields.
     *
     * @param Automation $automation
     * @param string $email
     * @param string|int $websiteId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function retrieveAutomationDataFields(Automation $automation, $email, $websiteId): array
    {
        $quoteItems = $this->getQuoteItems($automation->getTypeId());

        return $this->dataFieldUpdaterFactory->create()
            ->setDataFields(
                $email,
                $websiteId,
                $automation->getTypeId(),
                $automation->getStoreName(),
                $this->getNominatedAbandonedCartItem($quoteItems)
            )
            ->getData();
    }

    /**
     * Get (and store) quote items.
     *
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
