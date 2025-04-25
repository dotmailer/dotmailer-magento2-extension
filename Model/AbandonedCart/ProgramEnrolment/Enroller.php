<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment;

use Dotdigital\Exception\ResponseValidationException;
use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\AbandonedCart\CartInsight\Data as CartInsightData;
use Dotdigitalgroup\Email\Model\AbandonedCart\Interval;
use Dotdigitalgroup\Email\Model\AbandonedCart\TimeLimit;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\Contact\Patcher;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory as AutomationCollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Enroller
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CollectionFactory
     */
    private $orderCollection;

    /**
     * @var Interval
     */
    private $interval;

    /**
     * @var Patcher
     */
    private $patcher;

    /**
     * @var Saver
     */
    private $saver;

    /**
     * @var Rules
     */
    private $rules;

    /**
     * @var CartInsightData
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
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Logger $logger
     * @param CollectionFactory $collectionFactory
     * @param Interval $interval
     * @param Patcher $patcher
     * @param Saver $saver
     * @param Rules $rules
     * @param CartInsightData $cartInsight
     * @param AutomationCollectionFactory $automationFactory
     * @param TimeLimit $timeLimit
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Logger $logger,
        CollectionFactory $collectionFactory,
        Interval $interval,
        Patcher $patcher,
        Saver $saver,
        Rules $rules,
        CartInsightData $cartInsight,
        AutomationCollectionFactory $automationFactory,
        TimeLimit $timeLimit,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->logger = $logger;
        $this->orderCollection = $collectionFactory;
        $this->interval = $interval;
        $this->patcher = $patcher;
        $this->saver = $saver;
        $this->rules = $rules;
        $this->cartInsight = $cartInsight;
        $this->automationFactory = $automationFactory;
        $this->timeLimit = $timeLimit;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Process abandoned carts for program enrolment.
     *
     * @return void
     * @throws \Exception
     */
    public function process()
    {
        foreach ($this->storeManager->getStores() as $store) {
            $this->processAbandonedCartsProgramEnrolmentAutomation($store);
        }
    }

    /**
     * Process abandoned carts for automation program enrolment
     *
     * @param StoreInterface $store
     *
     * @return void
     * @throws \Exception
     */
    private function processAbandonedCartsProgramEnrolmentAutomation($store)
    {
        $storeId = $store->getId();
        $programId = $this->scopeConfig->getValue(
            Config::XML_PATH_LOSTBASKET_ENROL_TO_PROGRAM_ID,
            ScopeInterface::SCOPE_STORE,
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
                    $this->patcher->getOrCreateContactByEmail(
                        $quote->getCustomerEmail(),
                        (int) $store->getWebsiteId(),
                        (int) $storeId
                    );
                    $this->cartInsight->send($quote, $storeId);

                    if ($quote->hasItems()) {
                        $this->saveIfNotAlreadyInDatabase($quote, $store, $programId);
                    }
                } catch (ResponseValidationException $e) {
                    $this->logger->error(
                        sprintf(
                            '%s: %s',
                            'Error creating contact in abandoned cart enroller',
                            $e->getMessage()
                        ),
                        [$e->getDetails()]
                    );
                    continue;
                } catch (\Exception|\Http\Client\Exception $e) {
                    $this->logger->error((string) $e);
                    continue;
                }
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
        $batchSize = $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_SYNC_LIMIT,
            ScopeInterface::SCOPE_STORE
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
     * @param StoreInterface $store
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
     *
     * @return bool
     * @throws \Exception
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
