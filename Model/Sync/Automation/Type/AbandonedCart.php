<?php

namespace Dotdigitalgroup\Email\Model\Sync\Automation\Type;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Automation;
use Dotdigitalgroup\Email\Model\Newsletter\BackportedSubscriberLoader;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation as AutomationResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\Sales\QuoteFactory as DotdigitalQuoteFactory;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationProcessor;
use Dotdigitalgroup\Email\Model\Sync\Automation\ContactManager;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldCollector;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldTypeHandler;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\Updater\AbandonedCartFactory as AbandonedCartUpdaterFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Newsletter\Model\Subscriber;
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
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param ContactManager $contactManager
     * @param DataFieldCollector $dataFieldCollector
     * @param DataFieldTypeHandler $dataFieldTypeHandler
     * @param BackportedSubscriberLoader $backportedSubscriberLoader
     * @param AbandonedCartUpdaterFactory $dataFieldUpdaterFactory
     * @param DotdigitalQuoteFactory $ddgQuoteFactory
     * @param QuoteFactory $quoteFactory
     */
    public function __construct(
        Data $helper,
        Logger $logger,
        AutomationResource $automationResource,
        ContactCollectionFactory $contactCollectionFactory,
        ContactManager $contactManager,
        DataFieldCollector $dataFieldCollector,
        DataFieldTypeHandler $dataFieldTypeHandler,
        BackportedSubscriberLoader $backportedSubscriberLoader,
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
            $automationResource,
            $contactCollectionFactory,
            $contactManager,
            $dataFieldCollector,
            $dataFieldTypeHandler,
            $backportedSubscriberLoader
        );
    }

    /**
     * Check if automation should be processed.
     *
     * @param Automation $automation
     *
     * @return bool
     * @throws AlreadyExistsException
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
     * Check non-subscriber can be enrolled.
     *
     * For AC enrolment, this is governed by the switch in Configuration > Abandoned Carts.
     *
     * @param Subscriber $subscriber
     * @param Automation $automation
     *
     * @return void
     * @throws LocalizedException
     */
    protected function checkNonSubscriberCanBeEnrolled(Subscriber $subscriber, Automation $automation)
    {
        if (!$subscriber->isSubscribed() &&
            $this->helper->isOnlySubscribersForContactSync($automation->getWebsiteId()) &&
            $this->helper->isOnlySubscribersForAC($automation->getStoreId())
        ) {
            throw new LocalizedException(
                __('Non-subscribed contacts cannot be enrolled.')
            );
        }
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
