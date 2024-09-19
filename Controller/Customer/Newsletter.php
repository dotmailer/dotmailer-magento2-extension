<?php

namespace Dotdigitalgroup\Email\Controller\Customer;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Customer\Account\Configuration;
use Dotdigitalgroup\Email\Model\Customer\DataField\Date;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Store\Model\StoreManagerInterface;
use Dotdigitalgroup\Email\Model\Contact\ContactResponseHandler;

class Newsletter implements HttpPostActionInterface
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ContactResource
     */
    private $contactResource;

    /**
     * @var ContactCollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var Configuration
     */
    private $accountConfig;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var Validator
     */
    private $formKeyValidator;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @var SubscriberFactory
     */
    private $subscriberFactory;

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
     * @param ContactResource $contactResource
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param Configuration $accountConfig
     * @param Session $session
     * @param Context $context
     * @param RedirectFactory $resultRedirectFactory
     * @param Validator $formKeyValidator
     * @param ManagerInterface $messageManager
     * @param CustomerRepository $customerRepository
     * @param SubscriberFactory $subscriberFactory
     * @param StoreManagerInterface $storeManager
     * @param Date $dateField
     * @param ContactResponseHandler $contactResponseHandler
     */
    public function __construct(
        Data $helper,
        ContactResource $contactResource,
        ContactCollectionFactory $contactCollectionFactory,
        Configuration $accountConfig,
        Session $session,
        Context $context,
        RedirectFactory $resultRedirectFactory,
        Validator $formKeyValidator,
        ManagerInterface $messageManager,
        CustomerRepository $customerRepository,
        SubscriberFactory $subscriberFactory,
        StoreManagerInterface $storeManager,
        Date $dateField,
        ContactResponseHandler $contactResponseHandler
    ) {
        $this->helper = $helper;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->contactResource = $contactResource;
        $this->accountConfig = $accountConfig;
        $this->customerSession = $session;
        $this->request = $context->getRequest();
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->formKeyValidator = $formKeyValidator;
        $this->messageManager = $messageManager;
        $this->customerRepository = $customerRepository;
        $this->subscriberFactory = $subscriberFactory;
        $this->storeManager = $storeManager;
        $this->dateField = $dateField;
        $this->contactResponseHandler = $contactResponseHandler;
    }

    /**
     * Execute.
     *
     * @return Redirect
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        if (! $this->formKeyValidator->validate($this->request)) {
            return $this->resultRedirectFactory->create()
                ->setPath('customer/account/');
        }

        $this->processGeneralSubscription();

        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore();
        $websiteId = $store->getWebsiteId();

        if ($this->helper->isEnabled($websiteId)) {
            $customerEmail = $this->customerSession->getCustomer()->getEmail();
            $client = $this->helper->getWebsiteApiClient($websiteId);
            $contactId = $this->loadOrFetchContactId($customerEmail, $websiteId, $client);

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
        return $this->resultRedirectFactory->create()
            ->setPath('connector/customer/index/');
    }

    /**
     * Load or fetch contact id.
     *
     * @param string $customerEmail
     * @param int $websiteId
     * @param Client $client
     *
     * @return int
     * @throws LocalizedException
     */
    private function loadOrFetchContactId($customerEmail, $websiteId, $client)
    {
        $existingContact = $this->contactCollectionFactory->create()
            ->loadByCustomerEmail($customerEmail, $websiteId);

        return $existingContact->getContactId() ?
            $existingContact->getContactId() :
            $this->createContact($client, $existingContact);
    }

    /**
     * Create contact.
     *
     * @param Client $apiClient
     * @param Contact $contactFromTable
     *
     * @return int
     * @throws LocalizedException
     */
    private function createContact($apiClient, $contactFromTable)
    {
        $contact = $apiClient->postContactWithConsentAndPreferences(
            $contactFromTable->getEmail()
        );

        $contactId = $this->contactResponseHandler->getContactIdFromResponse($contact);

        if ($contactId) {
            $contactFromTable->setContactId($contactId);
            $this->contactResource->save($contactFromTable);
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
        $additionalSubscriptions = $this->request->getParam('additional_subscriptions', []);

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
        $paramDataFields = $this->request->getParam('data_fields', []);

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
             * Empty Dates will not be adjusted.
             */
            if (isset($processedFields[$key])) {
                if ($processedFields[$key] == 'Numeric') {
                    $paramDataFields[$key] = (int) $value;
                }
                if ($processedFields[$key] == 'String') {
                    $paramDataFields[$key] = (string) $value;
                }
                if ($processedFields[$key] == 'Date') {
                    if (!$value) {
                        $paramDataFields[$key] = '';
                    } else {
                        $paramDataFields[$key] = $this->dateField
                            ->getScopeAdjustedDate(
                                $this->storeManager->getStore()->getId(),
                                $value
                            );
                    }
                }
                if ($processedFields[$key] == 'Boolean') {
                    $paramDataFields[$key] = (bool) $value;
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
        $preferencesFromSession = $this->customerSession->getDmContactPreferences();
        if (empty($preferencesFromSession)) {
            return true;
        }

        $preferences = $this->processParamPreferences($this->request->getParam('preferences', []));
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
     * Process general subscription.
     *
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
                $isSubscribedParam = (bool) $this->request->getParam('is_subscribed', false);

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
