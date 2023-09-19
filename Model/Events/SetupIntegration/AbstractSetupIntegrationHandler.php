<?php

namespace Dotdigitalgroup\Email\Model\Events\SetupIntegration;

use Dotdigitalgroup\Email\Model\Events\EventInterface;

abstract class AbstractSetupIntegrationHandler implements EventInterface
{
    /**
     * Get Updated Data.
     *
     * @return string
     */
    abstract public function update(): string;

    /**
     * Encoded message for transport
     *
     * @param array $data
     * @return string
     */
    public function encode(array $data):string
    {
        return json_encode($data);
    }
}
