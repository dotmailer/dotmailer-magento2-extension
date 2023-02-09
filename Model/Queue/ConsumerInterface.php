<?php

namespace Dotdigitalgroup\Email\Model\Queue;

interface ConsumerInterface
{

    /**
     * Process the message
     *
     * @param string $message
     * @return void
     */
    public function process(string $message):void;
}
