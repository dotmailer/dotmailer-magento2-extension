<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Plugin;

use Dotdigitalgroup\Email\Model\ResourceModel\Catalog;
use Dotdigitalgroup\Email\Logger\Logger;
use Magento\Catalog\Controller\Adminhtml\Product\MassStatus;
use Magento\Framework\Controller\ResultInterface;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;

class CatalogMassStatusUpdatePlugin
{
    /**
     * @var Catalog
     */
    private $catalogResource;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var array
     */
    private $currentStatuses = [];

    /**
     * @param Catalog $catalogResource
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param Logger $logger
     */
    public function __construct(
        Catalog $catalogResource,
        Filter $filter,
        CollectionFactory $collectionFactory,
        Logger $logger
    ) {
        $this->catalogResource = $catalogResource;
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
    }

    /**
     * Before execute - capture current status values
     *
     * @param MassStatus $subject
     * @return null
     */
    public function beforeExecute(MassStatus $subject): null
    {
        try {
            /** @var Collection $collection */
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $collection->addAttributeToSelect('status');

            foreach ($collection as $product) {
                $this->currentStatuses[$product->getId()] = (int) $product->getStatus();
            }
        } catch (\Exception $e) {
            $this->logger->error('Error capturing current product statuses: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * After execute - compare with new status and update only changed products
     *
     * @param MassStatus $subject
     * @param ResultInterface $result
     * @return ResultInterface
     */
    public function afterExecute(MassStatus $subject, ResultInterface $result): ResultInterface
    {
        try {
            if (empty($this->currentStatuses)) {
                return $result;
            }

            $newStatus = (int) $subject->getRequest()->getParam('status');
            $changedProductIds = [];

            foreach ($this->currentStatuses as $productId => $currentStatus) {
                if ($currentStatus !== $newStatus) {
                    $changedProductIds[] = $productId;
                }
            }

            if (!empty($changedProductIds)) {
                $this->catalogResource->setUnprocessedByIds($changedProductIds);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error processing bulk status update: ' . $e->getMessage());
        }

        return $result;
    }
}
