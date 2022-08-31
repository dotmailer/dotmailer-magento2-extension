<?php

namespace Dotdigitalgroup\Email\Model\Sync;

use Magento\Framework\DataObject;

abstract class AbstractContactSyncer extends DataObject
{
    /**
     * Creates the mega batch.
     *
     * For any type of contact sync, batches will only ever be for one website.
     *
     * @param array $batch
     * @param array $megaBatch
     * @return array
     */
    protected function mergeBatch(array $batch, array $megaBatch)
    {
        foreach ($batch as $contactId => $data) {
            $megaBatch[$contactId] = $data;
        }
        return $megaBatch;
    }

    /**
     * Determines whether the sync was triggered from Configuration > Dotdigital > Developer > Sync Settings.
     *
     * @return bool
     */
    protected function isRunFromDeveloperButton()
    {
        return (bool)$this->_getData('web');
    }
}
