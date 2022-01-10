<?php

namespace Dotdigitalgroup\Email\Model;

use Dotdigitalgroup\Email\Model\ResourceModel\Contact;

class Subscriber
{
    /**
     * @var Contact
     */
    private $contact;

    /**
     * Subscriber constructor.
     * @param Contact $contact
     */
    public function __construct(
        Contact $contact
    ) {
        $this->contact = $contact;
    }

    /**
     * @param string|null $from
     * @param string|null $to
     * @return int
     */
    public function reset(string $from = null, string $to = null)
    {
        return $this->contact->resetSubscribers($from, $to);
    }
}
