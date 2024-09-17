<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Api\Model\Sync\Export;

/**
 * Interface SyncBuilderInterface
 *
 * This interface defines the methods required for building and exporting sync data.
 */
interface SdkBuilderInterface
{
    /**
     * Set the data to be built.
     *
     * @param mixed $data The data to be built.
     * @return SdkBuilderInterface Returns the instance of the builder.
     */
    public function setBuildableData($data): SdkBuilderInterface;

    /**
     * Build the data.
     *
     * @return mixed The built data.
     */
    public function build();
}
