<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Export;

use Dotdigital\V3\Models\Contact as SdkContact;
use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\WebsiteInterface;

interface ExporterInterface
{
    /**
     * Export data.
     *
     * @param array $data
     * @param WebsiteInterface $website
     * @param int $listId
     *
     * @return array<SdkContact>
     * @throws LocalizedException|Exception
     */
    public function export(array $data, WebsiteInterface $website, int $listId): array;

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
