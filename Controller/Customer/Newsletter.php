<?php

namespace Dotdigitalgroup\Email\Controller\Customer;

class Newsletter extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var \Magento\Customer\Model\Session
     */
    public $customerSession;
    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    public $formKeyValidator;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    public $localeDate;

    /**
     * Newsletter constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data                   $helper
     * @param \Magento\Customer\Model\Session                      $session
     * @param \Magento\Framework\App\Action\Context                $context
     * @param \Magento\Framework\Data\Form\FormKey\Validator       $formKeyValidator
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Customer\Model\Session $session,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
    ) {
        $this->helper           = $helper;
        $this->customerSession  = $session;
        $this->formKeyValidator = $formKeyValidator;
        $this->localeDate       = $localeDate;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        if (!$this->formKeyValidator->validate($this->getRequest())
            or !$this->customerSession->getConnectorContactId()
        ) {
            return $this->_redirect('customer/account/');
        }

        //params
        $additionalSubscriptions = $this->getRequest()->getParam(
            'additional_subscriptions'
        );
        $paramDataFields = $this->getRequest()->getParam(
            'data_fields'
        );
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
                $bookError = false;
                $addressBooks = $client->getContactAddressBooks(
                    $contact->id
                );
                $subscriberAddressBook = $this->helper->getSubscriberAddressBook(
                    $website
                );
                $processedAddressBooks = [];
                if (is_array($addressBooks)) {
                    foreach ($addressBooks as $addressBook) {
                        if ($subscriberAddressBook != $addressBook->id) {
                            $processedAddressBooks[$addressBook->id]
                                = $addressBook->name;
                        }
                    }
                }
                if (isset($additionalSubscriptions)) {
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
                    foreach ($processedAddressBooks as $bookId => $name) {
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
                } else {
                    foreach ($processedAddressBooks as $bookId => $name) {
                        $bookResponse = $client->deleteAddressBookContact(
                            $bookId,
                            $contact->id
                        );
                        if (isset($bookResponse->message)) {
                            $bookError = true;
                        }
                    }
                }

                //contact data fields
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
        $this->_redirect('customer/account/');
    }
}
