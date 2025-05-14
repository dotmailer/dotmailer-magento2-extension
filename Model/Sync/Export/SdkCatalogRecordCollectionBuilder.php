<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Export;

use Dotdigital\V3\Models\InsightData\Record;
use Dotdigital\V3\Models\InsightData\RecordsCollection;
use Dotdigitalgroup\Email\Api\Model\Sync\Export\SdkCollectionBuilderInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\ProductFactory as ConnectorProductFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Exception\SchemaValidationException;
use Exception;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;

class SdkCatalogRecordCollectionBuilder implements SdkCollectionBuilderInterface
{
    /**
     * @var ProductCollection
     */
    private $data;

    /**
     * @var ConnectorProductFactory
     */
    private $connectorProductFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var int
     */
    private $storeId;

    /**
     * @var int
     */
    private $customerGroupId;

    /**
     * @param ConnectorProductFactory $connectorProductFactory
     * @param Logger $logger
     * @param int $storeId
     * @param int|null $customerGroupId
     */
    public function __construct(
        ConnectorProductFactory $connectorProductFactory,
        Logger $logger,
        int $storeId,
        ?int $customerGroupId = null
    ) {
        $this->connectorProductFactory = $connectorProductFactory;
        $this->logger = $logger;
        $this->storeId = $storeId;
        $this->customerGroupId = $customerGroupId;
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
        foreach ($this->data as $product) {
            if ($product->getId()) {
                try {
                    $connectorProduct = $this->connectorProductFactory->create();
                    $connectorProduct->setProduct($product, $this->storeId, $this->customerGroupId);

                    $collection->set(
                        (string) $product->getId(),
                        new Record([
                            'key' => (string) $product->getId(),
                            'json' => $connectorProduct->toArray()
                        ])
                    );

                } catch (SchemaValidationException $exception) {
                    $this->logger->debug(
                        sprintf(
                            "Product id %s was not exported, but will be marked as processed in the context of a sync",
                            $product->getId()
                        ),
                        [$exception, $exception->errors()]
                    );

                } catch (Exception $exception) {
                    $this->logger->debug(
                        sprintf(
                            "Product id %s was not exported, but will be marked as processed in the context of a sync",
                            $product->getId()
                        ),
                        [$exception]
                    );

                }
            }
        }
        return $collection;
    }
}
