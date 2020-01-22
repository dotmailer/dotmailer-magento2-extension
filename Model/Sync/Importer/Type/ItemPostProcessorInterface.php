<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type;

interface ItemPostProcessorInterface
{
    /**
     * Legendary error message
     */
    const ERROR_UNKNOWN = 'Error unknown';

    /**
     * @param $item
     * @param $result
     * @return mixed
     */
    public function handleItemAfterSync($item, $result);
}
