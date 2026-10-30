<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Plugin;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Catalog\StoreLevelCatalogDeleter;
use Magento\Catalog\Model\Product\Action;

/**
 * Handles website unassignment via the Update attributes mass action.
 */
class ProductWebsiteUnassignPlugin
{
    /**
     * @var StoreLevelCatalogDeleter
     */
    private $deleter;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param StoreLevelCatalogDeleter $deleter
     * @param Logger $logger
     */
    public function __construct(StoreLevelCatalogDeleter $deleter, Logger $logger)
    {
        $this->deleter = $deleter;
        $this->logger = $logger;
    }

    /**
     * After updateWebsites - queue deletes for removed websites.
     *
     * @param Action $subject
     * @param mixed $result
     * @param array $productIds
     * @param array $websiteIds
     * @param string $type
     * @return mixed
     */
    public function afterUpdateWebsites(Action $subject, $result, $productIds, $websiteIds, $type)
    {
        if ($type !== 'remove') {
            return $result;
        }

        try {
            foreach (array_unique($productIds) as $productId) {
                $this->deleter->execute((int) $productId, (array) $websiteIds);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error removing products from store-level catalog: ' . $e->getMessage());
        }

        return $result;
    }
}
