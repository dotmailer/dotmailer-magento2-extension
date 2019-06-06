<?php

namespace Dotdigitalgroup\Email\Block\Customer\Account;

/**
 * Books block
 *
 * @api
 */
class Books extends \Magento\Framework\View\Element\Template
{
    /**
     * Apiconnector client.
     *
     * @var \Dotdigitalgroup\Email\Model\Apiconnector\Client
     */
    private $client;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    public $customerSession;

    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * @var object
     */
    private $contactFromAccount;

    /**
     * @var \Dotdigitalgroup\Email\Model\Contact
     */
    private $contactFromTable;


    /**
     * Books constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        array $data = []
    ) {
        $this->helper          = $helper;
        $this->customerSession = $customerSession;
        $this->subscriberFactory = $subscriberFactory;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getCustomerConsentText()
    {
        return $this->helper->configHelperFactory->create()
            ->getConsentCustomerText($this->getCustomer()->getWebsiteId());
    }

    /**
     * @return \Magento\Customer\Model\Customer
     */
    public function getCustomer()
    {
        return $this->customerSession->getCustomer();
    }

    /**
     * Subscription pref save url.
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('connector/customer/newsletter');
    }

    /**
     * Get config values.
     *
     * @param string $path
     * @param int $website
     *
     * @return string|boolean
     */
    public function _getWebsiteConfigFromHelper($path, $website)
    {
        return $this->helper->getWebsiteConfig($path, $website);
    }

    /**
     * Get api client.
     *
     * @return \Dotdigitalgroup\Email\Model\Apiconnector\Client
     */
    private function _getApiClient()
    {
        if (empty($this->client)) {
            $website = $this->getCustomer()->getStore()->getWebsite();
            $client = $this->helper->getWebsiteApiClient($website);
            $this->client = $client;
        }

        return $this->client;
    }

    /**
     * Can show additional books?
     *
     * @return string|boolean
     */
    public function getCanShowAdditionalBooks()
    {
        return $this->helper->getCanShowAdditionalSubscriptions(
            $this->getCustomer()->getStore()->getWebsite()
        );
    }

    /**
     * Getter for additional books. Fully processed.
     *
     * @return array
     */
    public function getAdditionalBooksToShow()
    {
        $additionalBooksToShow = [];
        $processedAddressBooks = [];
        $additionalFromConfig = $this->helper->getAddressBookIdsToShow($this->getCustomer()->getStore()->getWebsite());
        $contactFromTable = $this->getContactFromTable();
        if (! empty($additionalFromConfig) && $contactFromTable->getContactId()) {
            $contact = $this->getConnectorContact();
            if (isset($contact->id) && $contact->status !== 'PendingOptIn') {
                $addressBooks = $this->_getApiClient()
                    ->getContactAddressBooks(
                        $contact->id
                    );
                if (is_array($addressBooks)) {
                    foreach ($addressBooks as $addressBook) {
                        $processedAddressBooks[$addressBook->id]
                            = $addressBook->name;
                    }
                }
            }
        }

        return $this->getProcessedAdditionalBooks(
            $additionalFromConfig,
            $processedAddressBooks,
            $additionalBooksToShow
        );
    }

    /**
     * Can show data fields?
     *
     * @return string|boolean
     */
    public function getCanShowDataFields()
    {
        return $this->helper->getCanShowDataFields(
            $this->getCustomer()->getStore()->getWebsite()
        );
    }

    /**
     * Getter for datafields to show. Fully processed.
     *
     * @return array
     */
    public function getDataFieldsToShow()
    {
        $datafieldsToShow = [];
        $website = $this->getCustomer()->getStore()->getWebsite();
        $dataFieldsFromConfig = $this->_getWebsiteConfigFromHelper(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_SHOW_FIELDS,
            $website
        );

        if (empty($dataFieldsFromConfig)) {
            return $datafieldsToShow;
        }

        $processedContactDataFields = [];
        $processedConnectorDataFields = [];
        $contactFromTable = $this->getContactFromTable();
        $dataFieldsFromConfig = explode(',', $dataFieldsFromConfig);

        if ($contactFromTable->getContactId()) {
            $contact = $this->getConnectorContact();
            if (isset($contact->id)) {
                $contactDataFields = $contact->dataFields;
                foreach ($contactDataFields as $contactDataField) {
                    $processedContactDataFields[$contactDataField->key]
                        = $contactDataField->value;
                }
            }
        }

        return $this->getProcessedDataFieldsToShow(
            $processedConnectorDataFields,
            $dataFieldsFromConfig,
            $processedContactDataFields,
            $datafieldsToShow
        );
    }

    /**
     * Find out if anything is true.
     *
     * @return bool
     */
    public function canShowAnything()
    {
        if (! $this->isCustomerSubscriber() || ! $this->helper->isEnabled($this->getCustomer()->getWebsiteId())) {
            return false;
        }

        $showPreferences = $this->getCanShowPreferences();
        $books = $this->getAdditionalBooksToShow();
        $fields = $this->getDataFieldsToShow();
        if ($books || $fields || $showPreferences) {
            if (! empty($books) || ! empty($fields) || $showPreferences) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    private function isCustomerSubscriber()
    {
        return $this->subscriberFactory->create()
            ->loadByCustomerId($this->getCustomer()->getId())
            ->isSubscribed();
    }

    /**
     * Get connector contact.
     *
     * @return object
     */
    public function getConnectorContact()
    {
        if (! isset($this->contactFromAccount)) {
            $contact = $this->_getApiClient()->getContactByEmail(
                $this->getCustomer()->getEmail()
            );
            if (isset($contact->id)) {
                $this->contactFromAccount = $contact;
                $this->customerSession->setConnectorContactId($contact->id);
            }
        }

        return $this->contactFromAccount;
    }

    /**
     * @return \Dotdigitalgroup\Email\Model\Contact
     */
    private function getContactFromTable()
    {
        if (! isset($this->contactFromTable)) {
            $this->contactFromTable = $this->helper->getContactByEmail(
                $this->getCustomer()->getEmail(),
                $this->getCustomer()->getStore()->getWebsite()->getId()
            );
        }

        return $this->contactFromTable;
    }

    /**
     * @return array
     */
    public function getPreferencesToShow()
    {
        $processedPreferences = [];
        $showPreferences = $this->getCanShowPreferences();
        $contactFromTable = $this->getContactFromTable();

        if ($showPreferences && $contactFromTable->getContactId()) {
            $contact = $this->getConnectorContact();
            if (isset($contact->id)) {
                $preferences = $this->_getApiClient()->getPreferencesForContact($contact->id);
                if (is_array($preferences)) {
                    $processedPreferences = $this->processPreferences($preferences, $processedPreferences);
                }
            }
        } elseif ($showPreferences) {
            $preferences = $this->_getApiClient()->getPreferences();
            if (is_array($preferences)) {
                $processedPreferences = $this->processPreferences($preferences, $processedPreferences);
            }
        }
        $this->customerSession->setDmContactPreferences($processedPreferences);
        return $processedPreferences;
    }

    /**
     * @return bool
     */
    public function getCanShowPreferences()
    {
        return $this->_getWebsiteConfigFromHelper(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SHOW_PREFERENCES,
            $this->getCustomer()->getStore()->getWebsite()
        );
    }

    /**
     * @param $preferences
     * @param $processedPreferences
     *
     * @return mixed
     */
    private function processPreferences($preferences, $processedPreferences)
    {
        foreach ($preferences as $preference) {
            $formattedPreference = [];
            $formattedPreference['isPreference'] = $preference->isPreference;
            if (! $preference->isPreference) {
                if (! isset($preference->preferences)) {
                    continue;
                }
                $formattedPreference['catLabel'] = $preference->publicName;
                $formattedCatPreferences = [];
                foreach ($preference->preferences as $catPreference) {
                    $formattedCatPreference = [];
                    $formattedCatPreference['label'] = $catPreference->publicName;
                    isset($catPreference->isOptedIn)? $formattedCatPreference['isOptedIn'] = $catPreference->isOptedIn :
                        $formattedCatPreference['isOptedIn'] = false;
                    $formattedCatPreferences[$catPreference->id] = $formattedCatPreference;
                }
                $formattedPreference['catPreferences'] = $formattedCatPreferences;
            } else {
                $formattedPreference['label'] = $preference->publicName;
                isset($preference->isOptedIn)? $formattedPreference['isOptedIn'] = $preference->isOptedIn :
                    $formattedPreference['isOptedIn'] = false;
            }
            $processedPreferences[$preference->id] = $formattedPreference;
        }
        return $processedPreferences;
    }

    /**
     * @param $additionalFromConfig
     * @param $processedAddressBooks
     * @param $additionalBooksToShow
     *
     * @return array
     */
    private function getProcessedAdditionalBooks($additionalFromConfig, $processedAddressBooks, $additionalBooksToShow)
    {
        foreach ($additionalFromConfig as $bookId) {
            $connectorBook = $this->_getApiClient()->getAddressBookById(
                $bookId
            );
            if (isset($connectorBook->id)) {
                $subscribed = 0;
                if (isset($processedAddressBooks[$bookId])) {
                    $subscribed = 1;
                }
                $additionalBooksToShow[] = [
                    'name' => $connectorBook->name,
                    'value' => $connectorBook->id,
                    'subscribed' => $subscribed,
                ];
            }
        }
        return $additionalBooksToShow;
    }

    /**
     * @param array $processedConnectorDataFields
     * @param array $dataFieldsFromConfig
     * @param array $processedContactDataFields
     * @param array $datafieldsToShow
     *
     * @return array
     */
    private function getProcessedDataFieldsToShow(
        $processedConnectorDataFields,
        $dataFieldsFromConfig,
        $processedContactDataFields,
        $datafieldsToShow
    ) {
        $connectorDataFields = $this->_getApiClient()->getDataFields();
        if (! isset($connectorDataFields->message)) {
            foreach ($connectorDataFields as $connectorDataField) {
                $processedConnectorDataFields[$connectorDataField->name]
                    = $connectorDataField;
            }
            foreach ($dataFieldsFromConfig as $dataFieldFromConfig) {
                if (isset($processedConnectorDataFields[$dataFieldFromConfig])) {
                    $value = '';
                    if (isset($processedContactDataFields[$processedConnectorDataFields[$dataFieldFromConfig]->name])) {
                        if ($processedConnectorDataFields[$dataFieldFromConfig]->type
                            == 'Date'
                        ) {
                            $value = $processedContactDataFields[
                                $processedConnectorDataFields[$dataFieldFromConfig]->name
                            ];
                            $value = $this->_localeDate->convertConfigTimeToUtc($value, 'm/d/Y');
                        } else {
                            $value
                                = $processedContactDataFields[
                                    $processedConnectorDataFields[$dataFieldFromConfig]->name
                            ];
                        }
                    }

                    $datafieldsToShow[] = [
                        'name' => $processedConnectorDataFields[$dataFieldFromConfig]->name,
                        'type' => $processedConnectorDataFields[$dataFieldFromConfig]->type,
                        'value' => $value,
                    ];
                }
            }
        }
        return $datafieldsToShow;
    }

    /**
     * @return bool
     */
    public function isSubscribed()
    {
        $subscriber = $this->subscriberFactory->create()->loadByCustomerId(
            $this->customerSession->getCustomerId()
        );
        if ($subscriber->getId()) {
            return $subscriber->isSubscribed();
        }

        return false;
    }
}
