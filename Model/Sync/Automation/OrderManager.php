<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Automation;

use Dotdigital\Exception\ResponseValidationException;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\Automation;
use Dotdigitalgroup\Email\Model\Sync\Order\Exporter;
use Http\Client\Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as SalesOrderCollectionFactory;

class OrderManager
{
    private const ORDERS_INSIGHT_COLLECTION_NAME = 'Orders';

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var AutomationTypeHandler
     */
    private $automationTypeHandler;

    /**
     * @var Exporter
     */
    private $exporter;

    /**
     * @var SalesOrderCollectionFactory
     */
    private $salesOrderCollectionFactory;

    /**
     * @param Logger $logger
     * @param ClientFactory $clientFactory
     * @param AutomationTypeHandler $automationTypeHandler
     * @param Exporter $exporter
     * @param SalesOrderCollectionFactory $salesOrderCollectionFactory
     */
    public function __construct(
        Logger $logger,
        ClientFactory $clientFactory,
        AutomationTypeHandler $automationTypeHandler,
        Exporter $exporter,
        SalesOrderCollectionFactory $salesOrderCollectionFactory
    ) {
        $this->logger = $logger;
        $this->clientFactory = $clientFactory;
        $this->automationTypeHandler = $automationTypeHandler;
        $this->exporter = $exporter;
        $this->salesOrderCollectionFactory = $salesOrderCollectionFactory;
    }

    /**
     * Check if automation is order type, send order insight data if so.
     *
     * @param Automation $automation
     *
     * @return void
     */
    public function maybeSendOrderInsightData(Automation $automation)
    {
        if (!$this->automationTypeHandler->isOrderTypeAutomation($automation->getAutomationType())) {
            return;
        }

        $incrementId = $automation->getTypeId();
        $email = $automation->getEmail();
        $websiteId = (int) $automation->getWebsiteId();

        try {
            $this->prepareDotdigitalOrder($incrementId, $email, $websiteId);
        } catch (ResponseValidationException $e) {
            $this->logger->debug(
                sprintf(
                    '%s %s: %s',
                    'Error posting order data for order increment id',
                    $incrementId,
                    $e->getMessage()
                ),
                [$e->getDetails()]
            );
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }

    /**
     * Prepare Dotdigital order.
     *
     * @param string $incrementId
     * @param string $email
     * @param int $websiteId
     *
     * @return void
     * @throws LocalizedException
     * @throws ResponseValidationException
     * @throws Exception
     * @throws NoSuchEntityException
     */
    private function prepareDotdigitalOrder(string $incrementId, string $email, int $websiteId): void
    {
        $collection = $this->salesOrderCollectionFactory->create()
            ->addFieldToFilter('increment_id', $incrementId);

        $data = $this->exporter->mapOrderData($collection);
        if (empty($data) || !isset($data[$websiteId])) {
            throw new LocalizedException(
                __('No order data prepared for order increment id %1', $incrementId)
            );
        }

        $client = $this->clientFactory->create(
            ['data' => ['websiteId' => $websiteId]]
        );

        foreach ($data[$websiteId] as $order) {
            $client->insightData->createOrUpdateContactCollectionRecord(
                self::ORDERS_INSIGHT_COLLECTION_NAME,
                $incrementId,
                'email',
                $email,
                $order
            );
        }
    }
}
