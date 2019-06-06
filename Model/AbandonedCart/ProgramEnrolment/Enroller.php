<?php

namespace Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment;

use Dotdigitalgroup\Email\Model\Sync\SetsSyncFromTime;

class Enroller
{
    use SetsSyncFromTime;

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
     * Rules constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param Interval $interval
     * @param Saver $saver
     * @param Rules $rules
     * @param \Dotdigitalgroup\Email\Model\AbandonedCart\CartInsight\Data $cartInsight
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        Interval $interval,
        Saver $saver,
        Rules $rules,
        \Dotdigitalgroup\Email\Model\AbandonedCart\CartInsight\Data $cartInsight
    ) {
        $this->orderCollection = $collectionFactory;
        $this->helper = $data;
        $this->interval = $interval;
        $this->saver = $saver;
        $this->rules = $rules;
        $this->cartInsight = $cartInsight;
    }

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

        $updated = $this->interval->getAbandonedCartProgramEnrolmentWindow($storeId, $this->getSyncFromTime());

        $quoteCollection = $this->getStoreQuotesForGuestsAndCustomers($storeId, $updated);

        foreach ($quoteCollection as $quote) {
            $this->saver->save($quote, $store, $programId);
            $this->cartInsight->send($quote, $storeId);
        }
    }

    /**
     * Retrieve store quotes
     *
     * @param int $storeId
     * @param array $updated
     *
     * @return \Magento\Quote\Model\ResourceModel\Quote\Collection|\Magento\Sales\Model\ResourceModel\Order\Collection
     */
    private function getStoreQuotesForGuestsAndCustomers($storeId, $updated)
    {
        $salesCollection = $this->orderCollection->create()
            ->getStoreQuotesForGuestsAndCustomers($storeId, $updated);

        $this->rules->apply($salesCollection, $storeId);

        return $salesCollection;
    }
}
