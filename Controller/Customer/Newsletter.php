<?php

namespace Dotdigitalgroup\Email\Controller\Customer;

class Newsletter extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $localeDate;

    /**
     * Newsletter constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data                   $helper
     * @param \Magento\Customer\Model\Session                      $session
     * @param \Magento\Framework\App\Action\Context                $context
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Customer\Model\Session $session,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
    ) {
        $this->helper           = $helper;
        $this->customerSession  = $session;
        $this->localeDate       = $localeDate;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        if (!$this->customerSession->getConnectorContactId()) {
            return $this->_redirect('customer/account/');
        }

        //params
        $additionalSubscriptions = $this->getRequest()->getParam('additional_subscriptions');
        $paramDataFields = $this->getRequest()->getParam('data_fields');
        $customerId = $this->customerSession->getConnectorContactId();
        $customerEmail = $this->customerSession->getCustomer()
            ->getEmail();

        //client
        $website = $this->customerSession->getCustomer()->getStore()
            ->getWebsite();
        //if enabled
        if ($this->helper->isEnabled($website)) {
            $client = $this->helper->getWebsiteApiClient($website);
            $client->setApiUsername($this->helper->getApiUsername($website))
                ->setApiPassword($this->helper->getApiPassword($website));

            $contact = $client->getContactById($customerId);
            if (isset($contact->id)) {
                //contact address books
                $addressBooks = $client->getContactAddressBooks(
                    $contact->id
                );
                $subscriberAddressBook = $this->helper->getSubscriberAddressBook(
                    $website
                );

                $processedAddressBooks = $this->getProcessedAddressBooks($addressBooks, $subscriberAddressBook);

                if (isset($additionalSubscriptions)) {
                    $bookError = $this->processAdditionalSubscriptions(
                        $additionalSubscriptions,
                        $processedAddressBooks,
                        $client,
                        $contact
                    );
                } else {
                    $bookError = $this->deleteAddressBookContacts(
                        $processedAddressBooks,
                        $client,
                        $contact
                    );
                }

                //contact data fields
                $data = $this->getContactDataFields($client, $paramDataFields);

                $contactResponse = $client->updateContactDatafieldsByEmail(
                    $customerEmail,
                    $data
                );

                if (isset($contactResponse->message) && $bookError) {
                    $this->messageManager->addErrorMessage(
                        __(
                            'An error occurred while saving your subscription preferences.'
                        )
                    );
                } else {
                    $this->messageManager->addSuccessMessage(
                        __('The subscription preferences has been saved.')
                    );
                }
            } else {
                $this->messageManager->addErrorMessage(
                    __(
                        'An error occurred while saving your subscription preferences.'
                    )
                );
            }
        }
        return $this->_redirect('customer/account/');
    }

    /**
     * @param mixed $addressBooks
     * @param mixed $subscriberAddressBook
     *
     * @return array
     */
    private function getProcessedAddressBooks($addressBooks, $subscriberAddressBook)
    {
        $processedAddressBooks = [];
        if (is_array($addressBooks)) {
            foreach ($addressBooks as $addressBook) {
                if ($subscriberAddressBook != $addressBook->id) {
                    $processedAddressBooks[$addressBook->id]
                        = $addressBook->name;
                }
            }
        }
        return $processedAddressBooks;
    }

    /**
     * @param mixed $additionalSubscriptions
     * @param mixed $processedAddressBooks
     * @param mixed $client
     * @param mixed $contact
     *
     * @return bool
     */
    private function processAdditionalSubscriptions($additionalSubscriptions, $processedAddressBooks, $client, $contact)
    {
        $bookError = false;

        foreach ($additionalSubscriptions as $additionalSubscription) {
            if (!isset($processedAddressBooks[$additionalSubscription])) {
                $bookResponse = $client->postAddressBookContacts(
                    $additionalSubscription,
                    $contact
                );
                if (isset($bookResponse->message)) {
                    $bookError = true;
                }
            }
        }
        foreach ($processedAddressBooks as $bookId) {
            if (!in_array($bookId, $additionalSubscriptions)) {
                $bookResponse = $client->deleteAddressBookContact(
                    $bookId,
                    $contact->id
                );
                if (isset($bookResponse->message)) {
                    $bookError = true;
                }
            }
        }
        return $bookError;
    }

    /**
     * @param mixed $client
     * @param mixed $paramDataFields
     *
     * @return array
     */
    private function getContactDataFields($client, $paramDataFields)
    {
        $data = [];
        $dataFields = $client->getDataFields();
        $processedFields = [];
        foreach ($dataFields as $dataField) {
            $processedFields[$dataField->name] = $dataField->type;
        }
        foreach ($paramDataFields as $key => $value) {
            if (isset($processedFields[$key]) && $value) {
                if ($processedFields[$key] == 'Numeric') {
                    $paramDataFields[$key] = (int)$value;
                }
                if ($processedFields[$key] == 'String') {
                    $paramDataFields[$key] = (string)$value;
                }
                if ($processedFields[$key] == 'Date') {
                    $paramDataFields[$key] = $this->localeDate->date($value)->format(\Zend_Date::ISO_8601);
                }
                $data[] = [
                    'Key' => $key,
                    'Value' => $paramDataFields[$key],
                ];
            }
        }
        return $data;
    }

    /**
     * @param mixed $processedAddressBooks
     * @param mixed $client
     * @param mixed $contact
     *
     * @return bool
     */
    private function deleteAddressBookContacts($processedAddressBooks, $client, $contact)
    {
        foreach ($processedAddressBooks as $bookId) {
            $bookResponse = $client->deleteAddressBookContact(
                $bookId,
                $contact->id
            );
            if (isset($bookResponse->message)) {
                return true;
            }
        }
        return false;
    }
}
