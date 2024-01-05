<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Queue\Sales;

use Dotdigital\Exception\ResponseValidationException;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\AbandonedCart\CartInsight\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\Queue\Data\CartPhaseUpdateData;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory;
use Magento\Quote\Model\QuoteRepository;
use Magento\Store\Model\StoreManagerInterface;

class CartPhaseUpdateConsumer
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Data
     */
    private $cartInsightData;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var CollectionFactory
     */
    private $automationCollectionFactory;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Update constructor.
     *
     * @param Logger $logger
     * @param Data $cartInsightData
     * @param ClientFactory $clientFactory
     * @param CollectionFactory $automationCollectionFactory
     * @param QuoteRepository $quoteRepository
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Logger $logger,
        Data $cartInsightData,
        ClientFactory $clientFactory,
        CollectionFactory $automationCollectionFactory,
        QuoteRepository $quoteRepository,
        StoreManagerInterface $storeManager
    ) {
        $this->logger = $logger;
        $this->cartInsightData = $cartInsightData;
        $this->clientFactory = $clientFactory;
        $this->automationCollectionFactory = $automationCollectionFactory;
        $this->quoteRepository = $quoteRepository;
        $this->storeManager = $storeManager;
    }

    /**
     * Process.
     *
     * @param CartPhaseUpdateData $messageData
     *
     * @return void
     */
    public function process(CartPhaseUpdateData $messageData)
    {
        $matchingAutomation = $this->automationCollectionFactory->create()
            ->getAbandonedCartAutomationByQuoteId($messageData->getQuoteId());

        if ($matchingAutomation->getSize() == 0) {
            return;
        }

        try {
            $quote = $this->quoteRepository->get($messageData->getQuoteId());
            $store = $this->storeManager->getStore($messageData->getStoreId());

            $data = $this->cartInsightData->getPayload($quote, $store);
            $data['json']['cartPhase'] = 'ORDER_COMPLETE';

            $client = $this->clientFactory->create(
                ['data' => ['websiteId' => $store->getWebsiteId()]]
            );

            /** @var \Magento\Quote\Model\Quote $quote */
            $client->insightData->createOrUpdateContactCollectionRecord(
                'CartInsight',
                (string) $messageData->getQuoteId(),
                'email',
                $quote->getCustomerEmail(),
                $data
            );
        } catch (ResponseValidationException $e) {
            $this->logger->error(
                sprintf(
                    '%s: %s.',
                    'Cart phase update failed',
                    $e->getMessage()
                ),
                [$e->getDetails()]
            );
        } catch (\Exception $e) {
            $this->logger->error(
                "Cart phase update failed",
                [(string) $e]
            );
        }

        $this->logger->info(
            sprintf("Cart phase updated for quote id: %s", $messageData->getQuoteId())
        );
    }
}
