<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Export;

use Dotdigital\V3\Models\InsightData\Record;
use Dotdigital\V3\Models\InsightData\RecordsCollection;
use Dotdigitalgroup\Email\Api\Model\Sync\Export\SdkCollectionBuilderInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\OrderFactory as ConnectorOrderFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Exception\SchemaValidationException;
use Exception;
use Magento\Sales\Model\ResourceModel\Order\Collection as SalesOrderCollection;

class SdkOrderRecordCollectionBuilder implements SdkCollectionBuilderInterface
{
    /**
     * @var SalesOrderCollection
     */
    private $data;

    /**
     * @var ConnectorOrderFactory
     */
    private $connectorOrderFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param ConnectorOrderFactory $connectorOrderFactory
     * @param Logger $logger
     */
    public function __construct(
        ConnectorOrderFactory $connectorOrderFactory,
        Logger $logger
    ) {
        $this->connectorOrderFactory = $connectorOrderFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function setBuildableData($data): SdkCollectionBuilderInterface
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function build(): RecordsCollection
    {
        $collection = new RecordsCollection();
        foreach ($this->data as $order) {
            if ($order->getId()) {
                try {
                    $connectorOrder = $this->connectorOrderFactory->create();
                    $connectorOrder->setOrderData($order);

                    $collection->set(
                        (string) $order->getId(),
                        new Record([
                            'key' => (string) $order->getIncrementId(),
                            'json' => $connectorOrder->toArrayWithEmptyArrayCheck(),
                            'contactIdentity' => ['identifier' => 'email', 'value' => $order->getCustomerEmail()]
                        ])
                    );

                } catch (SchemaValidationException $exception) {
                    $this->logger->debug(
                        sprintf(
                            "Order id %s was not exported, but will be marked as processed in the context of a sync",
                            $order->getId()
                        ),
                        [$exception, $exception->errors()]
                    );

                } catch (Exception $exception) {
                    $this->logger->debug(
                        sprintf(
                            "Order id %s was not exported, but will be marked as processed in the context of a sync",
                            $order->getId()
                        ),
                        [$exception]
                    );

                }
            }
        }
        return $collection;
    }
}
