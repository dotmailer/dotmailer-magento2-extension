<?php

namespace Dotdigitalgroup\Email\Model;

use Dotdigitalgroup\Email\Model\ResourceModel\Contact;

class Subscriber
{
    public const TOPIC_NEWSLETTER_SUBSCRIPTION = 'ddg.newsletter.subscription';

    /**
     * @var Contact
     */
    private $contact;

    /**
     * Subscriber constructor.
     *
     * @param Contact $contact
     */
    public function __construct(
        Contact $contact
    ) {
        $this->contact = $contact;
    }

    /**
     * Reset subscribers.
     *
     * @param string|null $from
     * @param string|null $to
     * @return int
     */
    public function reset(?string $from = null, ?string $to = null)
    {
        return $this->contact->resetSubscribers($from, $to);
    }
}
