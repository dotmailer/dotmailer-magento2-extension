<?php

namespace Dotdigitalgroup\Email\Model\Connector;

abstract class AbstractConnectorModel
{
    /**
     * Class to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
