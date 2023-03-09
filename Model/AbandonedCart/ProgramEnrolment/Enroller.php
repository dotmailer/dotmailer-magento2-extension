<?php

namespace Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\AbandonedCart\Interval;
use Dotdigitalgroup\Email\Model\AbandonedCart\TimeLimit;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory as AutomationCollectionFactory;
use Magento\Quote\Model\Quote;

class Enroller
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderCollection;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var Interval
     */
    private $interval;

    /**
     * @var Saver
     */
    private $saver;

    /**
     * @var Rules
     */
    private $rules;

    /**
     * @var \Dotdigitalgroup\Email\Model\AbandonedCart\CartInsight\Data
     */
    private $cartInsight;

    /**
     * @var AutomationCollectionFactory
     */
    private $automationFactory;

    /**
     * @var TimeLimit
     */
    private $timeLimit;

    /**
     * @param Logger $logger
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param Interval $interval
     * @param Saver $saver
     * @param Rules $rules
     * @param \Dotdigitalgroup\Email\Model\AbandonedCart\CartInsight\Data $cartInsight
     * @param AutomationCollectionFactory $automationFactory
     * @param TimeLimit $timeLimit
     */
    public function __construct(
        Logger $logger,
        \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        Interval $interval,
        Saver $saver,
        Rules $rules,
        \Dotdigitalgroup\Email\Model\AbandonedCart\CartInsight\Data $cartInsight,
        AutomationCollectionFactory $automationFactory,
        TimeLimit $timeLimit
    ) {
        $this->logger = $logger;
        $this->orderCollection = $collectionFactory;
        $this->helper = $data;
        $this->interval = $interval;
        $this->saver = $saver;
        $this->rules = $rules;
        $this->cartInsight = $cartInsight;
        $this->automationFactory = $automationFactory;
        $this->timeLimit = $timeLimit;
    }

    /**
     * Process abandoned carts for program enrolment.
     *
     * @return void
     * @throws \Exception
     */
    public function process()
    {
        foreach ($this->helper->getStores() as $store) {
            $this->processAbandonedCartsProgramEnrolmentAutomation($store);
        }
    }

    /**
     * Process abandoned carts for automation program enrolment
     *
     * @param \Magento\Store\Api\Data\StoreInterface $store
     *
     * @return void
     * @throws \Exception
     */
    private function processAbandonedCartsProgramEnrolmentAutomation($store)
    {
        $storeId = $store->getId();
        $programId = $this->helper->getScopeConfig()->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_LOSTBASKET_ENROL_TO_PROGRAM_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (empty($programId) || $programId === "0") {
            return;
        }

        $updated = $this->interval->getAbandonedCartProgramEnrolmentWindow($storeId);

        $quoteCollection = $this->getStoreQuotesForGuestsAndCustomers($storeId, $updated);

        foreach ($quoteCollection as $batchQuoteCollection) {
            foreach ($batchQuoteCollection as $quote) {
                try {
                    if ($quote->hasItems()) {
                        $this->saveIfNotAlreadyInDatabase($quote, $store, $programId);
                    }
                } catch (\Exception $e) {
                    $this->logger->debug(
                        sprintf('Error checking items for quote ID: %s', $quote->getId()),
                        [(string) $e]
                    );
                }

                // Confirm that a contact has been created on EC
                $contact = $this->helper->getOrCreateContact($quote->getCustomerEmail(), $store->getWebsiteId());
                if (!$contact) {
                    continue;
                }
                $this->cartInsight->send($quote, $storeId);
            }
        }
    }

    /**
     * Retrieve store quotes
     *
     * @param int $storeId
     * @param array $updated
     * @return \Iterator
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getStoreQuotesForGuestsAndCustomers($storeId, $updated)
    {
        $batchSize = $this->helper->getScopeConfig()->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_LIMIT,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $initialCollection = $this->orderCollection
            ->create()
            ->getStoreQuotesForAutomationEnrollmentGuestsAndCustomers($storeId, $updated);

        $page = 1;
        $collectionSize = $initialCollection->getSize();

        for ($i = 0; $i < $collectionSize; $i += $batchSize) {

            $salesCollection = $this->orderCollection
                ->create()
                ->getStoreQuotesForAutomationEnrollmentGuestsAndCustomers($storeId, $updated);

            $salesCollection->setPageSize($batchSize)->setCurPage($page);
            $this->rules->apply($salesCollection, $storeId);

            $page++;
            yield $salesCollection;
        }
    }

    /**
     * Save the automation.
     *
     * @param Quote $quote
     * @param \Magento\Store\Api\Data\StoreInterface $store
     * @param int $programId
     * @throws \Exception
     */
    private function saveIfNotAlreadyInDatabase($quote, $store, $programId)
    {
        if ($this->isAutomationFoundInsideTimeLimit($quote, $store->getId())) {
            return;
        }

        $this->saver->save($quote, $store, $programId);
    }

    /**
     * Check if a matching automation has already been sent inside a limit.
     *
     * @param Quote $quote
     * @param string $storeId
     * @return bool
     */
    private function isAutomationFoundInsideTimeLimit($quote, $storeId)
    {
        $updated = $this->timeLimit->getAbandonedCartTimeLimit($storeId);

        if (!$updated) {
            return false;
        }

        try {
            $automations = $this->automationFactory->create()
                ->getAbandonedCartAutomationsForContactByInterval(
                    $quote->getCustomerEmail(),
                    $updated,
                    $storeId
                );
        } catch (\Exception $e) {
            return false;
        }

        return (bool) $automations->getSize();
    }
}
