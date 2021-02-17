<?php

namespace Dotdigitalgroup\Email\Controller\Customer;

use Dotdigitalgroup\Email\Model\ContactFactory;
use Dotdigitalgroup\Email\Model\Customer\DataField\Date;
use Dotdigitalgroup\Email\Model\Newsletter\CsvGenerator;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\StoreManagerInterface;

class Newsletter extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var ContactFactory
     */
    private $contactFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Dotdigitalgroup\Email\Model\ConsentFactory
     */
    private $consentFactory;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    private $formKeyValidator;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * @var CsvGenerator
     */
    private $csvGenerator;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Date
     */
    private $dateField;

    /**
     * Newsletter constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param ContactFactory $contactFactory
     * @param \Magento\Customer\Model\Session $session
     * @param \Dotdigitalgroup\Email\Model\ConsentFactory $consentFactory
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param CustomerRepository $customerRepository
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param StoreManagerInterface $storeManager
     * @param Date $dateField
     * @param CsvGenerator $csvGenerator
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        ContactFactory $contactFactory,
        \Magento\Customer\Model\Session $session,
        \Dotdigitalgroup\Email\Model\ConsentFactory $consentFactory,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        CustomerRepository $customerRepository,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        StoreManagerInterface $storeManager,
        Date $dateField,
        CsvGenerator $csvGenerator
    ) {
        $this->helper           = $helper;
        $this->contactFactory = $contactFactory;
        $this->customerSession  = $session;
        $this->consentFactory   = $consentFactory;
        $this->formKeyValidator = $formKeyValidator;
        $this->customerRepository = $customerRepository;
        $this->subscriberFactory = $subscriberFactory;
        $this->csvGenerator = $csvGenerator;
        $this->storeManager = $storeManager;
        $this->dateField = $dateField;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        if (! $this->formKeyValidator->validate($this->getRequest())) {
            return $this->_redirect('customer/account/');
        }

        $this->processGeneralSubscription();

        $store = $this->storeManager->getStore();
        $website = $store->getWebsite();

        //if enabled
        if ($this->helper->isEnabled($website->getId())) {
            $customerEmail = $this->customerSession->getCustomer()->getEmail();
            $contactFromTable = $this->contactFactory->create()
                ->loadByCustomerEmail($customerEmail, $website->getId());
            $contactId = $this->getContactId($contactFromTable);

            $client = $this->helper->getWebsiteApiClient($website);
            $contact = isset($contactId)
                ? $client->getContactById($contactId)
                : $this->createContact($client, $customerEmail, $store, $contactFromTable);

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

                $contactPreferencesSuccess = $this->processContactPreferences($client, $contact);

                if (! $contactDataFieldsSuccess || ! $additionalSubscriptionsSuccess || ! $contactPreferencesSuccess) {
                    $this->messageManager->addErrorMessage(
                        __(
                            'An error occurred while saving your subscription preferences.'
                        )
                    );
                } else {
                    $this->messageManager->addSuccessMessage(
                        __('Your subscription preferences have been saved.')
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
        return $this->_redirect('connector/customer/index/');
    }

    /**
     * @param $apiClient
     * @param string $customerEmail
     * @param \Magento\Store\Api\Data\StoreInterface $store
     * @param \Dotdigitalgroup\Email\Model\Contact $contactFromTable
     *
     * @return object
     */
    private function createContact($apiClient, $customerEmail, $store, $contactFromTable)
    {
        $consentModel = $this->consentFactory->create();
        $consentData = $consentModel->getFormattedConsentDataByContactForApi(
            $store->getWebsiteId(),
            $customerEmail
        );

        if (empty(! $consentData)) {
            $contactData = [
                'Email' => $customerEmail,
                'EmailType' => 'Html'
            ];
            $optInType = $this->csvGenerator->isOptInTypeDouble($store);
            if ($optInType) {
                $contactData['OptInType'] = 'Double';
            }

            $contact = $apiClient->postContactWithConsent(
                $contactData,
                $consentData
            );
        } else {
            $contact = $apiClient->postContacts(
                $customerEmail
            );
        }

        if (isset($contact->id)) {
            $contactFromTable->setContactId($contact->id);
            $this->helper->saveContact($contactFromTable);
        }

        return $contact;
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
            /*
             * Allow boolean "0" to pass (e.g. "No" for "Yes/No" select)
             * as well as any other truthy $value
             */
            if (isset($processedFields[$key]) && ($value || $value === "0")) {
                if ($processedFields[$key] == 'Numeric') {
                    $paramDataFields[$key] = (int)$value;
                }
                if ($processedFields[$key] == 'String') {
                    $paramDataFields[$key] = (string)$value;
                }
                if ($processedFields[$key] == 'Date') {
                    $paramDataFields[$key] = $this->dateField
                        ->getScopeAdjustedDate(
                            $this->storeManager->getStore()->getId(),
                            $value
                        );
                }
                if ($processedFields[$key] == 'Boolean') {
                    $paramDataFields[$key] = (bool)$value;
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
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\Client $client
     * @param Object $contact
     * @return bool
     */
    private function processContactPreferences($client, $contact)
    {
        $preferences = [];
        $paramPreferences = $this->getRequest()->getParam('preferences', []);
        $preferencesFromSession = $this->customerSession->getDmContactPreferences();

        if (empty($paramPreferences) || empty($preferencesFromSession)) {
            return true;
        }

        $preferences = $this->processParamPreferences($paramPreferences, $preferences);
        $preferences = $this->processPreferencesFromSession($preferencesFromSession, $preferences);

        foreach ($preferences as $id => $preference) {
            if (isset($preference["preferences"])) {
                $preferences[$id]["preferences"] = array_values($preference["preferences"]);
            }
        }

        $response = $client->setPreferencesForContact($contact->id, array_values($preferences));
        return !isset($response->message);
    }

    /**
     * @param array $paramPreferences
     * @param array $data
     * @return array
     */
    private function processParamPreferences($paramPreferences, $data)
    {
        foreach ($paramPreferences as $paramPreference) {
            $idsArray = explode(',', $paramPreference);
            if (count($idsArray) == 2) {
                if (isset($data[$idsArray[0]])) {
                    $catPref = [
                        "id" => $idsArray[1],
                        "isPreference" => true,
                        "isOptedIn" => true
                    ];
                    $data[$idsArray[0]]['preferences'][$idsArray[1]] = $catPref;
                } else {
                    $data[$idsArray[0]] = [
                        "id" => $idsArray[0],
                        "isPreference" => false,
                        "preferences" => [$idsArray[1] => [
                                "id" => $idsArray[1],
                                "isPreference" => true,
                                "isOptedIn" => true
                            ]
                        ]
                    ];
                }
            } else {
                $data[$idsArray[0]] = [
                    "id" => $idsArray[0],
                    "isPreference" => true,
                    "isOptedIn" => true
                ];
            }
        }
        return $data;
    }

    /**
     * @param array $preferencesFromSession
     * @param array $data
     *
     * @return array
     */
    private function processPreferencesFromSession($preferencesFromSession, $data)
    {
        foreach ($preferencesFromSession as $id => $preferenceFromSession) {
            if ($preferenceFromSession['isPreference'] && !isset($data[$id])) {
                $data[$id] = [
                    "id" => $id,
                    "isPreference" => true,
                    "isOptedIn" => false
                ];
            } elseif (!$preferenceFromSession['isPreference']) {
                foreach ($preferenceFromSession["catPreferences"] as $catPrefId => $catPreference) {
                    if (!isset($data[$id]["preferences"][$catPrefId])) {
                        $data[$id]["preferences"][$catPrefId] = [
                            "id" => $catPrefId,
                            "isPreference" => true,
                            "isOptedIn" => false
                        ];
                    }
                }
            }
        }
        return $data;
    }

    /**
     * @param $contactFromTable
     * @return mixed
     */
    private function getContactId($contactFromTable)
    {
        $contactId = null;

        if (!$this->customerSession->getConnectorContactId()) {
            $contactId = $this->customerSession->getConnectorContactId();
        } elseif ($contactFromTable->getContactId()) {
            $contactId = $contactFromTable->getContactId();
        }

        return $contactId;
    }

    /**
     * Process general subscription
     * See Magento\Newsletter\Controller\Manage\Save
     */
    private function processGeneralSubscription()
    {
        $customerId = $this->customerSession->getCustomerId();
        $message = null;
        $isSuccess = true;

        if ($customerId === null) {
            $isSuccess = false;
            $message = __('Something went wrong while saving your subscription.');
        } else {
            try {
                $customer = $this->customerRepository->getById($customerId);
                $storeId = $this->storeManager->getStore()->getId();
                $customer->setStoreId($storeId);

                $isSubscribedState = $this->getIsSubscribedState($customerId);
                $isSubscribedParam = (bool) $this->getRequest()->getParam('is_subscribed', false);

                if ($isSubscribedParam !== $isSubscribedState) {
                    $this->customerRepository->save($customer);
                    $subscribeModel = $this->subscriberFactory->create();

                    if ($isSubscribedParam) {
                        $subscribeModel->subscribeCustomerById($customerId);
                        $subscribeStatus = $subscribeModel->getStatus();

                        $message = $subscribeStatus == Subscriber::STATUS_SUBSCRIBED
                            ? __('We have saved your subscription.')
                            : __('A confirmation request has been sent.');
                    } else {
                        $subscribeModel->unsubscribeCustomerById($customerId);
                        $message = __('We have removed your newsletter subscription.');
                    }
                } else {
                    $message = __('We have updated your subscription.');
                }
            } catch (\Exception $e) {
                $isSuccess = false;
                $message = __('Something went wrong while saving your subscription.');
            }
        }

        if ($isSuccess) {
            $this->messageManager->addSuccessMessage($message);
        } else {
            $this->messageManager->addErrorMessage($message);
        }
    }

    /**
     * With Global Account Sharing, $customer->getExtensionAttributes()->getIsSubscribed() is not reliable,
     * because we can have multiple subscriptions per customer ID
     *
     * @param string|int $customerId
     * @return bool
     */
    private function getIsSubscribedState($customerId)
    {
        $subscriber = $this->subscriberFactory->create()
            ->loadByCustomerId($customerId);

        return $subscriber->isSubscribed();
    }
}
