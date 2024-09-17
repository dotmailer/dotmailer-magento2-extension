<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Api\Model\Sync\Export;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\WebsiteInterface;

/**
 * Interface ContactExporterInterface
 *
 * This interface extends the ExporterInterface and provides methods to export data and manage field mappings.
 */
interface ContactExporterInterface extends ExporterInterface
{
    /**
     * Export data.
     *
     * @param array $data
     * @param WebsiteInterface $website
     * @param int $listId
     *
     * @return array
     * @throws LocalizedException|Exception
     */
    public function export(array $data, WebsiteInterface $website, int $listId);

    /**
     * Set field mapping.
     *
     * @param WebsiteInterface $website
     *
     * @return void
     */
    public function setFieldMapping(WebsiteInterface $website): void;

    /**
     * Get field mapping.
     *
     * @return array
     */
    public function getFieldMapping(): array;
}
