<?php

namespace Dotdigitalgroup\Email\Controller\Customer;

class Newsletter extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $_formKeyValidator;

    /**
     * Newsletter constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Customer\Model\Session $session
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Customer\Model\Session $session,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
    ) {
        $this->_helper = $helper;
        $this->_customerSession = $session;
        $this->_formKeyValidator = $formKeyValidator;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())
            or !$this->_customerSession->getConnectorContactId()
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
        $customerId = $this->_customerSession->getConnectorContactId();
        $customerEmail = $this->_customerSession->getCustomer()
            ->getEmail();

        //client
        $website = $this->_customerSession->getCustomer()->getStore()
            ->getWebsite();
        $client = $this->_helper->getWebsiteApiClient($website);
        $client->setApiUsername($this->_helper->getApiUsername($website))
            ->setApiPassword($this->_helper->getApiPassword($website));

        $contact = $client->getContactById($customerId);
        if (isset($contact->id)) {
            //contact address books
            $bookError = false;
            $addressBooks = $client->getContactAddressBooks(
                $contact->id
            );
            $subscriberAddressBook = $this->_helper->getSubscriberAddressBook(
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
                            $additionalSubscription, $contact
                        );
                        if (isset($bookResponse->message)) {
                            $bookError = true;
                        }
                    }
                }
                foreach ($processedAddressBooks as $bookId => $name) {
                    if (!in_array($bookId, $additionalSubscriptions)) {
                        $bookResponse = $client->deleteAddressBookContact(
                            $bookId, $contact->id
                        );
                        if (isset($bookResponse->message)) {
                            $bookError = true;
                        }
                    }
                }
            } else {
                foreach ($processedAddressBooks as $bookId => $name) {
                    $bookResponse = $client->deleteAddressBookContact(
                        $bookId, $contact->id
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
                        $date = new \Zend_Date($value, 'Y/M/d');
                        $dataFields[$key] = $date->toString(
                            \Zend_Date::ISO_8601
                        );
                    }
                    $data[] = [
                        'Key' => $key,
                        'Value' => $dataFields[$key],
                    ];
                }
            }
            $contactResponse = $client->updateContactDatafieldsByEmail(
                $customerEmail, $data
            );

            if (isset($contactResponse->message) && $bookError) {
                $this->messageManager->addError(
                    __(
                        'An error occurred while saving your subscription preferences.'
                    )
                );
            } else {
                $this->messageManager->addSuccess(
                    __('The subscription preferences has been saved.')
                );
            }
        } else {
            $this->messageManager->addError(
                __(
                    'An error occurred while saving your subscription preferences.'
                )
            );
        }
        $this->_redirect('customer/account/');
    }
}
