<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type;

use Dotdigitalgroup\Email\Model\Importer as ImporterModel;

interface ItemPostProcessorInterface
{
    /**
     * Legendary error message
     */
    public const ERROR_UNKNOWN = 'Error unknown';

    /**
     * Handle item after sync.
     *
     * @param ImporterModel $item
     * @param mixed $result
     * @return mixed
     */
    public function handleItemAfterSync($item, $result);
}
