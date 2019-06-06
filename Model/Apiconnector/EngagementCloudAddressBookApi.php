<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

class EngagementCloudAddressBookApi extends Client
{
    /**
     * EngagementCloudAddressBookApi constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Dotdigitalgroup\Email\Helper\File $fileHelper
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Dotdigitalgroup\Email\Helper\File $fileHelper
    ) {
        parent::__construct($data, $fileHelper);
    }

    /**
     * @param $websiteId
     * @return $this
     */
    public function setRequiredDataForClient($websiteId)
    {
        $this->setApiUsername($this->helper->getApiUsername($websiteId))
            ->setApiPassword($this->helper->getApiPassword($websiteId));

        $apiEndpoint = $this->helper->getApiEndpoint($websiteId, $this);

        if ($apiEndpoint) {
            $this->setApiEndpoint($apiEndpoint);
        }

        return $this;
    }

    /**
     * Resubscribes a previously unsubscribed contact to a given address book
     *
     * @param int $addressBookId
     * @param string $email
     *
     * @return mixed
     */
    public function postAddressBookContactResubscribe($addressBookId, $email)
    {
        $contact = ['unsubscribedContact' => ['email' => $email]];
        $url = $this->getApiEndpoint() . Client::REST_ADDRESS_BOOKS . $addressBookId
            . '/contacts/resubscribe';
        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($contact);

        $response = $this->execute();

        if (isset($response->message)) {
            $message = 'POST ADDRESS BOOK CONTACT RESUBSCRIBE ' . $url . ', '
                . $response->message;
            $this->helper->debug('postAddressBookContactResubscribe', [$message]);
        }

        return $response;
    }
}