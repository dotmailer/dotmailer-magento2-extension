<?php

namespace Dotdigitalgroup\Email\Model\Contact;

use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ContactFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Magento\Framework\Exception\LocalizedException;

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
     * @deprecated
     * @see processContactResponse
     *
     * @param \stdClass $response
     * @param string $email
     * @param string|int $websiteId
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

    /**
     * Processes the response from a /contacts API endpoint.
     *
     * An updated method that a) handles different response structures
     * and throws an exception if there is a message or a missing contact id.
     *
     * @param \stdClass $response
     * @param string $email
     * @param string|int $websiteId
     *
     * @throws LocalizedException
     * @return \stdClass
     */
    public function processContactResponse($response, $email, $websiteId)
    {
        $contact = $this->contactFactory->create()
            ->loadByCustomerEmail($email, $websiteId);
        $status = $this->getStatusFromResponse($response);
        $contactId = $this->getContactIdFromResponse($response);

        if (isset($response->message)) {
            if ($response->message == Client::API_ERROR_CONTACT_SUPPRESSED) {
                $this->deactivateContact($contact);
            }
            throw new LocalizedException(__($response->message));
        }

        if ($status && !in_array($status, [StatusInterface::SUBSCRIBED, StatusInterface::PENDING_OPT_IN])) {
            throw new LocalizedException(
                __('Contact has invalid status: "%1".', $status)
            );
        }

        if (!$contactId) {
            throw new LocalizedException(
                __('No contact id in response.')
            );
        }

        $contact->setContactId($contactId);
        $this->contactResource->save($contact);

        return $response;
    }

    /**
     * Obtains a status from a response object.
     *
     * @param \stdClass $response
     *
     * @return string
     */
    public function getStatusFromResponse($response): string
    {
        if (isset($response->contact->status)) {
            return $response->contact->status;
        } elseif (isset($response->status)) {
            return $response->status;
        }

        return '';
    }

    /**
     * Obtains a contact id from a response object.
     *
     * @param \stdClass $response
     *
     * @return int
     */
    public function getContactIdFromResponse($response): int
    {
        if (isset($response->contact->id)) {
            return $response->contact->id;
        } elseif (isset($response->id)) {
            return $response->id;
        }

        return 0;
    }

    /**
     * Deactivate contact i.e. mark as imported AND suppressed.
     *
     * @param Contact $contact
     *
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function deactivateContact(Contact $contact): void
    {
        $contact->setEmailImported(1);
        $contact->setSuppressed(1);
        $this->contactResource->save($contact);
    }
}
