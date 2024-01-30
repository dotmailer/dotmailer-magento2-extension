<?php

namespace Dotdigitalgroup\Email\Model\Contact;

use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\Contact;

class ContactDeactivator
{
    /**
     * @var ContactResource
     */
    private $contactResource;

    /**
     * @param ContactResource $contactResource
     */
    public function __construct(
        ContactResource $contactResource
    ) {
        $this->contactResource = $contactResource;
    }

    /**
     * Deactivate contact i.e. mark as imported AND suppressed.
     *
     * @param Contact $contact
     *
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function deactivateContact(Contact $contact): void
    {
        $contact->setEmailImported(1);
        $contact->setSuppressed(1);
        $this->contactResource->save($contact);
    }
}
