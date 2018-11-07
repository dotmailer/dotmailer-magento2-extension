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
     * @var object
     */
    public $client;

    /**
     * Contact id.
     *
     * @var string
     */
    public $contactId;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    public $customerSession;

    /**
     * Books constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Customer\Model\Session $customerSession,
        array $data = []
    ) {
        $this->helper          = $helper;
        $this->customerSession = $customerSession;
        parent::__construct($context, $data);
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
     * * Get api client.
     *
     * @return bool|mixed|object
     */
    public function _getApiClient()
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
        $website = $this->getCustomer()->getStore()->getWebsite();
        $additionalFromConfig = $this->helper->getAddressBookIdsToShow($website);

        if (! empty($additionalFromConfig)) {
            $this->getConnectorContact();
            if ($this->contactId) {
                $addressBooks = $this->_getApiClient()
                    ->getContactAddressBooks(
                        $this->contactId
                    );
                $processedAddressBooks = [];
                if (is_array($addressBooks)) {
                    foreach ($addressBooks as $addressBook) {
                        $processedAddressBooks[$addressBook->id]
                            = $addressBook->name;
                    }
                }
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
            }
        }

        return $additionalBooksToShow;
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
        $dataFieldsFromConfig = $this->_getWebsiteConfigFromHelper(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_SHOW_FIELDS,
            $this->getCustomer()->getStore()->getWebsite()
        );

        if (! empty($dataFieldsFromConfig)) {
            $dataFieldsFromConfig = explode(',', $dataFieldsFromConfig);
            $contact = $this->getConnectorContact();
            if ($this->contactId) {
                $contactDataFields = $contact->dataFields;
                $processedContactDataFields = [];
                foreach ($contactDataFields as $contactDataField) {
                    $processedContactDataFields[$contactDataField->key]
                        = $contactDataField->value;
                }

                $connectorDataFields = $this->_getApiClient()
                    ->getDataFields();
                $processedConnectorDataFields = [];
                foreach ($connectorDataFields as $connectorDataField) {
                    $processedConnectorDataFields[$connectorDataField->name]
                        = $connectorDataField;
                }
                foreach ($dataFieldsFromConfig as $dataFieldFromConfig) {
                    if (isset($processedConnectorDataFields[$dataFieldFromConfig])) {
                        $value = '';
                        if (isset($processedContactDataFields[$processedConnectorDataFields[
                                                              $dataFieldFromConfig]->name])) {
                            if ($processedConnectorDataFields[$dataFieldFromConfig]->type
                                == 'Date'
                            ) {
                                $value
                                       = $processedContactDataFields[$processedConnectorDataFields[
                                                                     $dataFieldFromConfig]->name];
                                $value = $this->_localeDate->convertConfigTimeToUtc($value, 'm/d/Y');
                            } else {
                                $value
                                    = $processedContactDataFields[$processedConnectorDataFields[
                                                                  $dataFieldFromConfig]->name];
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
        }

        return $datafieldsToShow;
    }

    /**
     * Find out if anything is true.
     *
     * @return bool
     */
    public function canShowAnything()
    {
        if ($this->getCanShowDataFields() or $this->getCanShowAdditionalBooks()
        ) {
            $books = $this->getAdditionalBooksToShow();
            $fields = $this->getDataFieldsToShow();
            if (!empty($books) or !empty($fields)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get connector contact.
     *
     * @return object
     */
    public function getConnectorContact()
    {
        $contact = $this->_getApiClient()->getContactByEmail(
            $this->getCustomer()->getEmail()
        );

        if (isset($contact->id)) {
            $this->customerSession->setConnectorContactId($contact->id);
            $this->contactId = $contact->id;
        } else {
            $contact = $this->_getApiClient()->postContacts(
                $this->getCustomer()->getEmail()
            );
            if (isset($contact->id)) {
                $this->customerSession->setConnectorContactId($contact->id);
                $this->contactId = $contact->id;
            }
        }

        return $contact;
    }

    /**
     * Getter for contact id.
     *
     * @return int|string
     */
    public function getConnectorContactId()
    {
        return $this->contactId;
    }
}
