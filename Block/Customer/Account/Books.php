<?php

namespace Dotdigitalgroup\Email\Block\Customer\Account;

class Books extends \Magento\Framework\View\Element\Template
{
    /**
     * Apiconnector client.
     *
     * @var object
     */
    protected $_client;
    /**
     * Contact id.
     *
     * @var string
     */
    protected $contactId;
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * Books constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->_helper = $helper;
        $this->customerSession = $customerSession;
        parent::__construct($context, $data);
    }

    /**
     * @return \Magento\Customer\Model\Customer
     */
    protected function getCustomer()
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
     * @param $path
     * @param $website
     *
     * @return mixed
     */
    protected function _getWebsiteConfigFromHelper($path, $website)
    {
        return $this->_helper->getWebsiteConfig($path, $website);
    }

    /**
     * * Get api client.
     *
     * @return bool|mixed|object
     */
    protected function _getApiClient()
    {
        if (empty($this->_client)) {
            $website = $this->getCustomer()->getStore()->getWebsite();
            $client = $this->_helper->getWebsiteApiClient($website);
            $client->setApiUsername($this->_helper->getApiUsername($website))
                ->setApiPassword($this->_helper->getApiPassword($website));
            $this->_client = $client;
        }

        return $this->_client;
    }

    /**
     * Can show additional books?
     *
     * @return mixed
     */
    public function getCanShowAdditionalBooks()
    {
        return $this->_getWebsiteConfigFromHelper(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_CAN_CHANGE_BOOKS,
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
        $additionalFromConfig = $this->_getWebsiteConfigFromHelper(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_SHOW_BOOKS,
            $this->getCustomer()->getStore()->getWebsite()
        );

        if (strlen($additionalFromConfig)) {
            $additionalFromConfig = explode(',', $additionalFromConfig);
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
     * @return mixed
     */
    public function getCanShowDataFields()
    {
        return $this->_getWebsiteConfigFromHelper(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_CAN_SHOW_FIELDS,
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

        if (strlen($dataFieldsFromConfig)) {
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
     * @return mixed
     */
    public function getConnectorContact()
    {
        $contact = $this->_getApiClient()->getContactByEmail(
            $this->getCustomer()->getEmail()
        );
        if ($contact->id) {
            $this->customerSession->setConnectorContactId($contact->id);
            $this->contactId = $contact->id;
        } else {
            $contact = $this->_getApiClient()->postContacts(
                $this->getCustomer()->getEmail()
            );
            if ($contact->id) {
                $this->customerSession->setConnectorContactId($contact->id);
                $this->contactId = $contact->id;
            }
        }

        return $contact;
    }

    /**
     * Getter for contact id.
     *
     * @return mixed
     */
    public function getConnectorContactId()
    {
        return $this->contactId;
    }
}
