<?php

namespace Dotdigitalgroup\Email\Model\Events;

class CloseStreamHandler implements EventInterface
{
    /**
     * Event Process
     *
     * @return string
     */
    public function update(): string
    {
        return json_encode([
            "success"=>true,
            "data"=>"Stream Closed"
        ]);
    }
}
