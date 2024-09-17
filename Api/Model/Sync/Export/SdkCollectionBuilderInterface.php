<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Api\Model\Sync\Export;

use Dotdigital\V3\Models\InsightData\RecordsCollection;

interface SdkCollectionBuilderInterface extends SdkBuilderInterface
{
    /**
     * Build a RecordsCollection.
     *
     * @return RecordsCollection
     */
    public function build(): RecordsCollection;
}
