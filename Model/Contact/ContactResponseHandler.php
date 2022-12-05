<?php

namespace Dotdigitalgroup\Email\Model\Contact;

use Dotdigitalgroup\Email\Model\ContactFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\StatusInterface;

class ContactResponseHandler
{
    /**
     * @var ContactFactory
     */
    private $contactFactory;

    /**
     * @var ContactResource
     */
    private $contactResource;

    /**
     * ContactResponseHandler constructor.
     *
     * @param ContactFactory $contactFactory
     * @param ContactResource $contactResource
     */
    public function __construct(
        ContactFactory $contactFactory,
        ContactResource $contactResource
    ) {
        $this->contactFactory = $contactFactory;
        $this->contactResource = $contactResource;
    }

    /**
     * Processes the response from a /contacts API endpoint.
     *
     * This code was originally located in the getOrCreateContact helper method.
     *
     * @param $response
     * @param $email
     * @param $websiteId
     *
     * @return bool|\stdClass
     */
    public function updateContactFromResponse($response, $email, $websiteId)
    {
        $contact = $this->contactFactory->create()
            ->loadByCustomerEmail($email, $websiteId);

        if (isset($response->status) &&
            !in_array($response->status, [StatusInterface::SUBSCRIBED, StatusInterface::PENDING_OPT_IN])
        ) {
            $contact->setEmailImported(1);
            $contact->setSuppressed(1);
            $this->contactResource->save($contact);
            return false;
        }

        if (isset($response->id)) {
            $contact->setContactId($response->id);
            $this->contactResource->save($contact);
        } else {
            return false;
        }

        return $response;
    }
}
