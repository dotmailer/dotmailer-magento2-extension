<?php

namespace Dotdigitalgroup\Email\Controller\Customer;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ContactFactory;
use Dotdigitalgroup\Email\Model\Customer\Account\Configuration;
use Dotdigitalgroup\Email\Model\Customer\DataField\Date;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Store\Model\StoreManagerInterface;
use Dotdigitalgroup\Email\Model\Contact\ContactResponseHandler;

class Newsletter extends Action
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ContactFactory
     */
    private $contactFactory;

    /**
     * @var Configuration
     */
    private $accountConfig;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var Validator
     */
    private $formKeyValidator;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Date
     */
    private $dateField;

    /**
     * @var ContactResponseHandler
     */
    private $contactResponseHandler;

    /**
     * @param Data $helper
     * @param ContactFactory $contactFactory
     * @param Configuration $accountConfig
     * @param Session $session
     * @param Context $context
     * @param Validator $formKeyValidator
     * @param CustomerRepository $customerRepository
     * @param SubscriberFactory $subscriberFactory
     * @param StoreManagerInterface $storeManager
     * @param Date $dateField
     * @param ContactResponseHandler $contactResponseHandler
     */
    public function __construct(
        Data $helper,
        ContactFactory $contactFactory,
        Configuration $accountConfig,
        Session $session,
        Context $context,
        Validator $formKeyValidator,
        CustomerRepository $customerRepository,
        SubscriberFactory $subscriberFactory,
        StoreManagerInterface $storeManager,
        Date $dateField,
        ContactResponseHandler $contactResponseHandler
    ) {
        $this->helper = $helper;
        $this->contactFactory = $contactFactory;
        $this->accountConfig = $accountConfig;
        $this->customerSession = $session;
        $this->formKeyValidator = $formKeyValidator;
        $this->customerRepository = $customerRepository;
        $this->subscriberFactory = $subscriberFactory;
        $this->storeManager = $storeManager;
        $this->dateField = $dateField;
        $this->contactResponseHandler = $contactResponseHandler;
        parent::__construct($context);
    }

    /**
     * Execute.
     *
     * @return ResponseInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        if (! $this->formKeyValidator->validate($this->getRequest())) {
            return $this->_redirect('customer/account/');
        }

        $this->processGeneralSubscription();

        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore();
        $websiteId = $store->getWebsiteId();

        if ($this->helper->isEnabled($websiteId)) {
            $customerEmail = $this->customerSession->getCustomer()->getEmail();
            $contactFromTable = $this->contactFactory->create()
                ->loadByCustomerEmail($customerEmail, $websiteId);
            $contactId = $this->getContactId($contactFromTable);
            $client = $this->helper->getWebsiteApiClient($websiteId);

            if (!$contactId) {
                $contactId = $this->createContact($client, $customerEmail, $contactFromTable);
            }

            if ($contactId) {
                $additionalSubscriptionsSuccess = $this->processAdditionalSubscriptions(
                    $customerEmail,
                    $contactId,
                    $client,
                    $websiteId
                );

                $contactDataFieldsSuccess = $this->processContactDataFields(
                    $customerEmail,
                    $client,
                    $websiteId
                );

                $contactPreferencesSuccess = $this->processContactPreferences($client, $contactId);

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
     * Create contact.
     *
     * @param Client $apiClient
     * @param string $customerEmail
     * @param Contact $contactFromTable
     *
     * @return int
     * @throws LocalizedException
     */
    private function createContact($apiClient, $customerEmail, $contactFromTable)
    {
        $contact = $apiClient->postContactWithConsentAndPreferences(
            $customerEmail
        );

        $contactId = $this->contactResponseHandler->getContactIdFromResponse($contact);

        if ($contactId) {
            $contactFromTable->setContactId($contactId);
            $this->helper->saveContact($contactFromTable);
        }

        return $contactId;
    }

    /**
     * Process additional subscriptions.
     *
     * @param string $customerEmail
     * @param string|int $contactId
     * @param Client $client
     * @param int $websiteId
     *
     * @return bool
     * @throws LocalizedException
     */
    private function processAdditionalSubscriptions($customerEmail, $contactId, $client, $websiteId)
    {
        $additionalFromConfig = $this->accountConfig->getAddressBookIdsToShow($websiteId);

        if (!$this->accountConfig->canShowAddressBooks($websiteId) ||
            empty($additionalFromConfig)) {
            return true;
        }

        $success = true;
        $additionalSubscriptions = $this->getRequest()->getParam('additional_subscriptions', []);

        foreach ($additionalFromConfig as $bookId) {
            if (in_array($bookId, $additionalSubscriptions)) {
                $bookResponse = $client->addContactToAddressBook(
                    $customerEmail,
                    $bookId
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
                    $contactId
                );
            }
        }
        return $success;
    }

    /**
     * Process contact data fields.
     *
     * @param string $customerEmail
     * @param Client $client
     * @param string|int $websiteId
     *
     * @return bool - success
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function processContactDataFields($customerEmail, $client, $websiteId)
    {
        $paramDataFields = $this->getRequest()->getParam('data_fields', []);

        if (!$this->accountConfig->canShowDataFields($websiteId) ||
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
     * Get data fields.
     *
     * @param Client $client
     * @param array $paramDataFields
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
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
     * Process contact preferences.
     *
     * @param Client $client
     * @param string|int $contactId
     *
     * @return bool
     * @throws LocalizedException
     */
    private function processContactPreferences(Client $client, $contactId): bool
    {
        $paramPreferences = $this->getRequest()->getParam('preferences', []);
        $preferencesFromSession = $this->customerSession->getDmContactPreferences();

        if (empty($paramPreferences) || empty($preferencesFromSession)) {
            return true;
        }

        $preferences = $this->processParamPreferences($paramPreferences);
        $this->augmentPreferencesFromSession($preferencesFromSession, $preferences);

        foreach ($preferences as $id => $preference) {
            if (isset($preference["preferences"])) {
                $preferences[$id]["preferences"] = array_values($preference["preferences"]);
            }
        }

        $response = $client->setPreferencesForContact($contactId, array_values($preferences));
        return !isset($response->message);
    }

    /**
     * Process preferences.
     *
     * @param array $paramPreferences
     * @return array
     */
    private function processParamPreferences($paramPreferences)
    {
        $data = [];
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
     * Add session preferences to the submitted payload.
     *
     * This effectively handles opt-outs. If there's a value checked in
     * the session but it hasn't been submitted, that's an opt out.
     *
     * @param array $preferencesFromSession
     * @param array $submittedPreferences
     */
    private function augmentPreferencesFromSession($preferencesFromSession, &$submittedPreferences)
    {
        foreach ($preferencesFromSession as $id => $preferenceFromSession) {
            if ($preferenceFromSession['isPreference'] && !isset($submittedPreferences[$id])) {
                $submittedPreferences[$id] = [
                    "id" => $id,
                    "isPreference" => true,
                    "isOptedIn" => false
                ];
                continue;
            }

            if (!isset($submittedPreferences[$id])) {
                $submittedPreferences[$id]  = [
                    "id" => $id,
                    "isPreference" => false,
                    "preferences" => []
                ];
            }

            if (isset($preferenceFromSession['catPreferences'])) {
                foreach ($preferenceFromSession["catPreferences"] as $catPrefId => $catPreference) {
                    if (!isset($submittedPreferences[$id]["preferences"][$catPrefId])) {
                        $submittedPreferences[$id]["preferences"][$catPrefId] = [
                            "id" => $catPrefId,
                            "isPreference" => true,
                            "isOptedIn" => false
                        ];
                    }
                }
            }
        }
    }

    /**
     * Get contact id.
     *
     * @param Contact $contactFromTable
     * @return int|null
     */
    private function getContactId($contactFromTable)
    {
        if ($contactFromTable->getContactId()) {
            return $contactFromTable->getContactId();
        }

        return null;
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
     * Get isSubscribed.
     *
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
