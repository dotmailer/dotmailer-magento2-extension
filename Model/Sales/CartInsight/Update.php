<?php

namespace Dotdigitalgroup\Email\Model\Sales\CartInsight;

use Dotdigitalgroup\Email\Model\AbandonedCart\CartInsight\Data;

class Update
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory
     */
    private $automationFactory;

    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var Data
     */
    private $abandonedCartData;

    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    private $importerFactory;

    /**
     * Update constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory $automationFactory
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param Data $abandonedCartData
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory $automationFactory,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        Data $abandonedCartData,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
    )
    {
        $this->automationFactory = $automationFactory;
        $this->quoteRepository = $quoteRepository;
        $this->abandonedCartData = $abandonedCartData;
        $this->importerFactory = $importerFactory;
    }

    /**
     * Update cart phase
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Store\Api\Data\StoreInterface $store
     *
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateCartPhase($order, $store)
    {
        $matchingAutomation = $this->automationFactory->create()
            ->getAbandonedCartAutomationByQuoteId($order->getQuoteId());

        if ($matchingAutomation->getSize()) {

            // Fetch the original quote data posted for the abandoned cart
            $quote = $this->quoteRepository->get($order->getQuoteId());
            $data = $this->abandonedCartData->getPayload($quote, $store);

            // Set the cartPhase flag
            $data['json']['cartPhase'] = 'ORDER_COMPLETE';

            $this->importerFactory->create()
                ->registerQueue(
                    \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_CART_INSIGHT_CART_PHASE,
                    $data,
                    \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE,
                    $store->getWebsite()->getId()
                );
        }
    }
}
