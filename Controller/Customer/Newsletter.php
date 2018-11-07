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
     * @param \Dotdigitalgroup\Email\Helper\Data                            $helper
     * @param \Magento\Customer\Model\Session                               $session
     * @param \Magento\Framework\App\Action\Context                         $context
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface          $localeDate
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
        $customerId = $this->customerSession->getConnectorContactId();
        $customerEmail = $this->customerSession->getCustomer()
            ->getEmail();

        //client
        $website = $this->customerSession->getCustomer()->getStore()
            ->getWebsite();

        //if enabled
        if ($this->helper->isEnabled($website)) {
            $client = $this->helper->getWebsiteApiClient($website);
            $contact = $client->getContactById($customerId);

            if (isset($contact->id)) {
                $additionalSubscriptionsSuccess = $this->processAdditionalSubscriptions(
                    $contact,
                    $client,
                    $website
                );

                $contactDataFieldsSuccess = $this->processContactDataFields(
                    $customerEmail,
                    $client,
                    $website
                );

                if (!$contactDataFieldsSuccess || !$additionalSubscriptionsSuccess) {
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
     * @param Object $contact
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\Client $client
     * @param \Magento\Store\Model\Website $website
     *
     * @return bool
     */
    private function processAdditionalSubscriptions($contact, $client, $website)
    {
        $additionalFromConfig = $this->helper->getAddressBookIdsToShow($website);

        if (!$this->helper->getCanShowAdditionalSubscriptions($website) ||
            empty($additionalFromConfig)) {
            return true;
        }

        $success = true;
        $additionalSubscriptions = $this->getRequest()->getParam('additional_subscriptions', []);

        foreach ($additionalFromConfig as $bookId) {
            if (in_array($bookId, $additionalSubscriptions)) {
                $bookResponse = $client->postAddressBookContacts(
                    $bookId,
                    $contact
                );
                if (isset($bookResponse->message)) {
                    $success = false;
                }
            }
        }
        foreach ($additionalFromConfig as $bookId) {
            if (!in_array($bookId, $additionalSubscriptions)) {
                $client->deleteAddressBookContact(
                    $bookId,
                    $contact->id
                );
            }
        }
        return $success;
    }

    /**
     * @param string $customerEmail
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\Client $client
     * @param \Magento\Store\Model\Website $website
     *
     * @return bool - success
     */
    private function processContactDataFields($customerEmail, $client, $website)
    {
        $paramDataFields = $this->getRequest()->getParam('data_fields', []);

        if (!$this->helper->getCanShowDataFields($website) ||
            empty($paramDataFields)) {
            return true;
        }

        $data = $this->getDataFields($client, $paramDataFields);

        $contactResponse = $client->updateContactDatafieldsByEmail(
            $customerEmail,
            $data
        );

        return !isset($contactResponse->message);
    }

    /**
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\Client $client
     * @param array $paramDataFields
     * @return array
     */
    private function getDataFields($client, $paramDataFields)
    {
        $data = [];
        $dataFields = $client->getDataFields();
        $processedFields = [];
        foreach ($dataFields as $dataField) {
            $processedFields[$dataField->name] = $dataField->type;
        }
        foreach ($paramDataFields as $key => $value) {
            if (isset($processedFields[$key])) {
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
}
