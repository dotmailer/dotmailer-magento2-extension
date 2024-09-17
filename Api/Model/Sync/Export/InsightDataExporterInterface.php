<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Api\Model\Sync\Export;

use Dotdigital\V3\Models\InsightData\Record;
use Magento\Store\Api\Data\WebsiteInterface;

/**
 * Interface InsightDataExporterInterface
 *
 * This interface extends the ExporterInterface and provides a method to build a collection of insight data records.
 */
interface InsightDataExporterInterface extends ExporterInterface
{
    /**
     * Build a collection of insight data records.
     *
     * @param array $data
     * @return array<string, Record>
     */
    public function export(array $data): array;
}
