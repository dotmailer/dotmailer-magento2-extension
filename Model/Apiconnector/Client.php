<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

use Dotdigitalgroup\Email\Logger\Logger;

/**
 * dotdigital REST V2 api client.
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Client extends \Dotdigitalgroup\Email\Model\Apiconnector\Rest
{
    const APICONNECTOR_VERSION = 'V2';

    const REST_WAIT_UPLOAD_TIME = 5;
    //rest api data
    const REST_ACCOUNT_INFO = 'https://r1-api.dotmailer.com/v2/account-info';
    const REST_CONTACTS = '/v2/contacts/';
    const REST_CONTACT_WITH_CONSENT = '/v2/contacts/with-consent';
    const REST_CONTACT_WITH_CONSENT_AND_PREFERENCES = '/v2/contacts/with-consent-and-preferences';
    const REST_CONTACTS_IMPORT = '/v2/contacts/import/';
    const REST_ADDRESS_BOOKS = '/v2/address-books/';
    const REST_DATA_FIELDS = '/v2/data-fields';
    const REST_TRANSACTIONAL_DATA_IMPORT = '/v2/contacts/transactional-data/import/';
    const REST_TRANSACTIONAL_DATA = '/v2/contacts/transactional-data/';
    const REST_CAMPAIGN_SEND = '/v2/campaigns/send';
    const REST_CONTACTS_SUPPRESSED_SINCE = '/v2/contacts/suppressed-since/';
    const REST_DATA_FIELDS_CAMPAIGNS = '/v2/campaigns';
    const REST_CONTACTS_RESUBSCRIBE = '/v2/contacts/resubscribe';
    const REST_CAMPAIGN_FROM_ADDRESS_LIST = '/v2/custom-from-addresses';
    const REST_CREATE_CAMPAIGN = '/v2/campaigns';
    const REST_PROGRAM = '/v2/programs/';
    const REST_PROGRAM_ENROLMENTS = '/v2/programs/enrolments';
    const REST_TEMPLATES = '/v2/templates';
    const REST_SEND_TRANSACTIONAL_EMAIL = '/v2/email';
    const REST_CAMPAIGNS_WITH_PREPARED_CONTENT = 'prepared-for-transactional-email';
    const REST_POST_ABANDONED_CART_CARTINSIGHT = '/v2/contacts/transactional-data/cartInsight';
    const REST_CHAT_SETUP = '/v2/webchat/setup';
    const REST_SURVEYS_FORMS = '/v2/surveys';

    //rest error responses
    const API_ERROR_API_EXCEEDED = 'Your account has generated excess API activity and is being temporarily capped. ' .
        'Please contact support. ERROR_APIUSAGE_EXCEEDED';
    const API_ERROR_TRANS_ALLOWANCE = 'TransactionalDataAllowanceInMegabytes';
    const API_ERROR_EMAIL_NOT_VALID = 'Email is not a valid email address. ERROR_PARAMETER_INVALID';
    const API_ERROR_FEATURENOTACTIVE = 'Error: ERROR_FEATURENOTACTIVE';
    const API_ERROR_REPORT_NOT_FOUND = 'Import is not processed yet or completed with error. ' .
        'ERROR_IMPORT_REPORT_NOT_FOUND';
    const API_ERROR_TRANS_NOT_EXISTS = 'Error: ERROR_TRANSACTIONAL_DATA_DOES_NOT_EXIST';
    const API_ERROR_DATAFIELD_EXISTS = 'Field already exists. ERROR_NON_UNIQUE_DATAFIELD';
    const API_ERROR_CONTACT_NOT_FOUND = 'Error: ERROR_CONTACT_NOT_FOUND';
    const API_ERROR_PROGRAM_NOT_ACTIVE = 'Error: ERROR_PROGRAM_NOT_ACTIVE';
    const API_ERROR_ENROLMENT_EXCEEDED = 'Error: ERROR_ENROLMENT_ALLOWANCE_EXCEEDED ';
    const API_ERROR_SEND_NOT_PERMITTED = 'Send not permitted at this time. ERROR_CAMPAIGN_SENDNOTPERMITTED';
    const API_ERROR_CONTACT_SUPPRESSED = 'Contact is suppressed. ERROR_CONTACT_SUPPRESSED';
    const API_ERROR_AUTHORIZATION_DENIED = 'Authorization has been denied for this request.';
    const API_ERROR_ADDRESSBOOK_NOT_FOUND = 'Error: ERROR_ADDRESSBOOK_NOT_FOUND';
    const API_ERROR_ADDRESSBOOK_DUPLICATE = 'That name is in use already, please choose another. ' .
        'ERROR_ADDRESSBOOK_DUPLICATE';
    const REST_ACCOUNT_PREFERENCES = "/v2/preferences";

    /**
     * @var string
     */
    private $apiEndpoint;

    /**
     * Excluded api response that we don't want to send.
     *
     * @var array
     */
    private $excludeMessages = [
        self::API_ERROR_FEATURENOTACTIVE,
        self::API_ERROR_PROGRAM_NOT_ACTIVE,
        self::API_ERROR_CONTACT_SUPPRESSED,
        self::API_ERROR_DATAFIELD_EXISTS,
        self::API_ERROR_AUTHORIZATION_DENIED,
        self::API_ERROR_ENROLMENT_EXCEEDED,
        self::API_ERROR_SEND_NOT_PERMITTED,
        self::API_ERROR_TRANS_NOT_EXISTS,
        self::API_ERROR_ADDRESSBOOK_NOT_FOUND,
    ];

    /**
     * @param string $apiEndpoint
     * @return void
     */
    public function setApiEndpoint($apiEndpoint)
    {
        $this->apiEndpoint = trim($apiEndpoint);
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getApiEndpoint()
    {
        if ($this->apiEndpoint === null) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Connector API endpoint cannot be empty.')
            );
        }

        return $this->apiEndpoint;
    }

    /**
     * Api validation.
     *
     * @param string $apiUsername
     * @param string $apiPassword
     *
     * @return bool|mixed
     */
    public function validate($apiUsername, $apiPassword)
    {
        if ($apiUsername && $apiPassword) {
            $this->setApiUsername($apiUsername)
                ->setApiPassword($apiPassword);
            $accountInfo = $this->getAccountInfo();

            if (isset($accountInfo->message)) {
                $this->addClientLog('Error validating API credentials', [], Logger::ERROR);
                return false;
            }

            return $accountInfo;
        }

        return false;
    }

    /**
     * Gets a contact by ID. Unsubscribed or suppressed contacts will not be retrieved.
     *
     * @param string $id
     *
     * @return null
     * @throws \Exception
     */
    public function getContactById($id)
    {
        $url = $this->getApiEndpoint() . self::REST_CONTACTS . $id;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Get contact info error');
        }

        return $response;
    }

    /**
     * Bulk creates, or bulk updates, contacts. Import format can either be CSV or Excel.
     * Must include one column called "Email". Any other columns will attempt to map to your custom data fields.
     * The ID of returned object can be used to query import progress.
     *
     * @param string|int $filename
     * @param string|int $addressBookId
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function postAddressBookContactsImport($filename, $addressBookId)
    {
        $url = $this->getApiEndpoint() . "/v2/address-books/{$addressBookId}/contacts/import";
        // @codingStandardsIgnoreStart
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt(
            $ch,
            CURLOPT_USERPWD,
            $this->getApiUsername() . ':' . $this->getApiPassword()
        );
        // @codingStandardsIgnoreEnd

        //case the deprecation of @filename for uploading
        if (function_exists('curl_file_create')) {
            $args['file']
                // @codingStandardsIgnoreStart
                = curl_file_create(
                    $this->fileHelper->getFilePathWithFallback($filename),
                    'text/csv'
                );
            curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
        // @codingStandardsIgnoreEnd
        } else {
            //standard use of curl file
            // @codingStandardsIgnoreLine
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'file' => '@' . $this->fileHelper->getFilePathWithFallback($filename),
            ]);
        }

        // @codingStandardsIgnoreStart
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: multipart/form-data',
            ]);
        // send contacts to address book
        $result = curl_exec($ch);
        // @codingStandardsIgnoreEnd

        $result = json_decode($result);

        if (isset($result->message)) {
            $this->addClientLog('Address book contacts import', [
                'address_book_id' => $addressBookId,
                'file' => $filename,
            ]);
        }

        return $result;
    }

    /**
     * Adds a contact to a given address book.
     *
     * @param string|int $addressBookId
     * @param string|int $apiContact
     *
     * @return mixed|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function postAddressBookContacts($addressBookId, $apiContact)
    {
        $url = $this->getApiEndpoint() . self::REST_ADDRESS_BOOKS . $addressBookId
            . '/contacts';
        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($apiContact);

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Address book contacts error');
        }

        return $response;
    }

    public function postAbandonedCartCartInsight($content)
    {
        $url = $this->getApiEndpoint() . self::REST_POST_ABANDONED_CART_CARTINSIGHT;
        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($content);

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Cart insight error');
        }

        return $response;
    }

    /**
     * Deletes all contacts from a given address book.
     *
     * @param string|int $addressBookId
     * @param string|int $contactId
     *
     * @return null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteAddressBookContact($addressBookId, $contactId)
    {
        //Only if there is a contact id and address book id
        if ($addressBookId && $contactId) {
            $url = $this->getApiEndpoint() . self::REST_ADDRESS_BOOKS . $addressBookId
                . '/contacts/' . $contactId;
            $this->setUrl($url)
                ->setVerb('DELETE');
            $this->execute();

            $this->addClientLog('Deleted contact from address book', [
                'contact_id' => $contactId,
                'address_book_id' => $addressBookId,
            ], Logger::DEBUG);
        }
    }

    /**
     * Gets a report with statistics about what was successfully imported, and what was unable to be imported.
     *
     * @param string|int $importId
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getContactsImportReport($importId)
    {
        $url = $this->getApiEndpoint() . self::REST_CONTACTS_IMPORT . $importId
            . '/report';
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Contacts import report', [], Logger::DEBUG);
        }

        return $response;
    }

    /**
     * Gets a contact by email address.
     *
     * @param string $email
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getContactByEmail($email)
    {
        $url = $this->getApiEndpoint() . self::REST_CONTACTS . $email;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error fetching contact by email', [
                'email' => $email,
                'message' => $response->message,
            ], Logger::DEBUG);
        }

        return $response;
    }

    /**
     * Get all address books.
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function getAddressBooks()
    {
        $url = $this->getApiEndpoint() . self::REST_ADDRESS_BOOKS;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();
        if (isset($response->message)) {
            $this->addClientLog('Error fetching address books');
        }

        return $response;
    }

    /**
     * Gets an address book by ID.
     *
     * @param int $id
     *
     * @return null
     * @throws \Exception
     */
    public function getAddressBookById($id)
    {
        $url = $this->getApiEndpoint() . self::REST_ADDRESS_BOOKS . $id;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error getting address book', [
                'address_book_id' => $id,
            ]);
        }

        return $response;
    }

    /**
     * Creates an address book.
     *
     * @param string $name
     * @param string $visibility
     *
     * @return null
     * @throws \Exception
     */
    public function postAddressBooks($name, $visibility = 'Public')
    {
        $data = [
            'Name' => $name,
            'Visibility' => $visibility,
        ];
        $url = $this->getApiEndpoint() . self::REST_ADDRESS_BOOKS;
        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($data);

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error creating address book');
        }

        return $response;
    }

    /**
     * Get list of all campaigns.
     *
     * @param int $skip     Number of campaigns to skip
     * @param int $select   Number of campaigns to select
     * @return mixed
     *
     * @throws \Exception
     */
    public function getCampaigns($skip = 0, $select = 1000)
    {
        $url = sprintf(
            '%s%s?select=%s&skip=%s',
            $this->getApiEndpoint(),
            self::REST_DATA_FIELDS_CAMPAIGNS,
            $select,
            $skip
        );
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error getting campaigns');
        }

        return $response;
    }

    /**
     * @param int $campaignId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCampaignById($campaignId)
    {
        $url = $this->getApiEndpoint() . self::REST_DATA_FIELDS_CAMPAIGNS . '/' . $campaignId;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error getting campaign', [
                'campaign_id' => $campaignId,
            ]);
        }

        return $response;
    }

    /**
     * @param int $campaignId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCampaignByIdWithPreparedContent($campaignId)
    {
        $url = $this->getApiEndpoint() . self::REST_DATA_FIELDS_CAMPAIGNS
            . '/' . $campaignId
            . '/' . self::REST_CAMPAIGNS_WITH_PREPARED_CONTENT
            . '/' . 'anonymouscontact@emailsim.io';
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error getting prepared content for campaign', [
                'campaign_id' => $campaignId,
            ]);
        }

        return $response;
    }

    /**
     * Creates a data field within the account.
     *
     * @param        $data         string/array
     * @param string $type string, numeric, date, boolean
     * @param string $visibility public, private
     * @param bool $defaultValue
     *
     * @return object
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function postDataFields(
        $data,
        $type = 'String',
        $visibility = 'public',
        $defaultValue = false
    ) {
        $url = $this->getApiEndpoint() . self::REST_DATA_FIELDS;
        //set default value for the numeric datatype
        if ($type == 'numeric' && !$defaultValue) {
            $defaultValue = 0;
        }
        //set data for the string datatype
        if (is_string($data)) {
            $data = [
                'Name' => $data,
                'Type' => $type,
                'Visibility' => $visibility,
            ];
            //default value
            if ($defaultValue) {
                $data['DefaultValue'] = $defaultValue;
            }
        }
        $this->setUrl($url)
            ->buildPostBody($data)
            ->setVerb('POST');

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error creating data fields')
                ->addClientLog('Failed data field creation', [
                    'data' => $data,
                ], Logger::DEBUG);
        }

        return $response;
    }

    /**
     * Lists the data fields within the account.
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getDataFields()
    {
        $url = $this->getApiEndpoint() . self::REST_DATA_FIELDS;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();
        if (isset($response->message)) {
            $this->addClientLog('Error fetching data fields');
        }

        return $response;
    }

    /**
     * Updates a contact.
     *
     * @param string|int $contactId
     * @param array $data
     *
     * @return object
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateContact($contactId, $data)
    {
        $url = $this->getApiEndpoint() . self::REST_CONTACTS . $contactId;
        $this->setUrl($url)
            ->setVerb('PUT')
            ->buildPostBody($data);

        $response = $this->execute();
        if (isset($response->message)) {
            $this->addClientLog('Error updating single contact', [
                'contact_id' => $contactId,
            ])
                ->addClientLog('Failed contact data', [
                    'data' => $data,
                ], Logger::DEBUG);
        }

        return $response;
    }

    /**
     * Deletes a contact.
     *
     * @param int $contactId
     *
     * @return null
     * @throws \Exception
     */
    public function deleteContact($contactId)
    {
        if ($contactId) {
            $url = $this->getApiEndpoint() . self::REST_CONTACTS . $contactId;
            $this->setUrl($url)
                ->setVerb('DELETE');

            $response = $this->execute();

            if (isset($response->message)) {
                $this->addClientLog('Error deleting contact');
            }

            return $response;
        }
    }

    /**
     * Update contact datafields by email.
     *
     * @param string $email
     * @param array $dataFields
     *
     * @return mixed|null
     *
     * @throws \Exception
     */
    public function updateContactDatafieldsByEmail($email, $dataFields)
    {
        $apiContact = $this->postContacts($email);
        //do not create for non contact id set
        if (!isset($apiContact->id)) {
            return $apiContact;
        } else {
            //get the contact id for this email
            $contactId = $apiContact->id;
        }
        $data = [
            'Email' => $email,
            'EmailType' => 'Html',
        ];
        $data['DataFields'] = $dataFields;
        $url = $this->getApiEndpoint() . self::REST_CONTACTS
            . $contactId;
        $this->setUrl($url)
            ->setVerb('PUT')
            ->buildPostBody($data);

        $response = $this->execute();
        if (isset($response->message)) {
            $this->addClientLog('Error updating contact data field')
                ->addClientLog('Failed contact field data', [
                    'data' => $data,
                ], Logger::DEBUG);
        }

        return $response;
    }

    /**
     * Sends a specified campaign to one or more address books, segments or contacts at a specified time.
     * Leave the address book array empty to send to All Contacts.
     *
     * @param int $campaignId
     * @param array $contacts
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function postCampaignsSend($campaignId, $contacts)
    {
        $data = [
            'username' => $this->getApiUsername(),
            'password' => $this->getApiPassword(),
            'campaignId' => $campaignId,
            'ContactIds' => $contacts,
        ];
        $this->setUrl($this->getApiEndpoint() . self::REST_CAMPAIGN_SEND)
            ->setVerb('POST')
            ->buildPostBody($data);

        $response = $this->execute();
        if (isset($response->message)) {
            unset($data['password']);

            $this->addClientLog('Error sending campaign', [
                'campaign_id' => $campaignId,
            ])
                ->addClientLog('Failed campaign send data', [
                    'data' => $data,
                ], Logger::DEBUG);
        }

        return $response;
    }

    /**
     * Creates a contact.
     *
     * @param string $email
     *
     * @return null
     * @throws \Exception
     */
    public function postContacts($email)
    {
        $url = $this->getApiEndpoint() . self::REST_CONTACTS;
        $data = [
            'Email' => $email,
            'EmailType' => 'Html',
        ];
        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($data);

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error adding contacts');
        }

        return $response;
    }

    /**
     * Gets a list of suppressed contacts after a given date along with the reason for suppression.
     *
     * @param string $dateString
     * @param int $select
     * @param int $skip
     *
     * @return object
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getContactsSuppressedSinceDate(
        $dateString,
        $select = 1000,
        $skip = 0
    ) {
        $url = $this->getApiEndpoint() . self::REST_CONTACTS_SUPPRESSED_SINCE
            . $dateString . '?select=' . $select . '&skip=' . $skip;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error getting suppressed contacts')
                ->addClientLog('Suppressed contacts request parameters', [
                    'since_date' => $dateString,
                    'select' => $select,
                    'skip' => $skip,
                ], Logger::DEBUG);
        }

        return $response;
    }

    /**
     * Adds multiple pieces of transactional data to contacts asynchronously,
     * returning an identifier that can be used to check for import progress.
     * @param array  $transactionalData
     * @param string $collectionName
     *
     * @return null
     * @throws \Exception
     */
    public function postContactsTransactionalDataImport(
        $transactionalData,
        $collectionName = 'Orders'
    ) {
        $orders = [];
        foreach ($transactionalData as $one) {
            if (isset($one['email'])) {
                $orders[] = [
                    'Key' => $one['id'],
                    'ContactIdentifier' => $one['email'],
                    'Json' => json_encode($one),
                ];
            }
        }
        $url = $this->getApiEndpoint() . self::REST_TRANSACTIONAL_DATA_IMPORT
            . $collectionName;
        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($orders);

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error in bulk import of transactional data for contacts')
                ->addClientLog('Failed transactional data', [
                    'collection' => $collectionName,
                    'data' => $orders
                ]);
        }

        return $response;
    }

    /**
     * Adds a single piece of transactional data to a contact.
     *
     * @param array $data
     * @param string $collectionName
     *
     * @return null
     * @throws \Exception
     */
    public function postContactsTransactionalData(
        $data,
        $collectionName = 'Orders'
    ) {
        $order = $this->getContactsTransactionalDataByKey(
            $collectionName,
            $data['id']
        );
        if (!isset($order->key) || isset($order->message)
            && $order->message == self::API_ERROR_TRANS_NOT_EXISTS
        ) {
            $url = $this->getApiEndpoint() . self::REST_TRANSACTIONAL_DATA
                . $collectionName;
        } else {
            $url = $this->getApiEndpoint() . self::REST_TRANSACTIONAL_DATA
                . $collectionName . '/' . $order->key;
        }
        $apiData = [
            'Key' => $data['id'],
            'ContactIdentifier' => $data['email'],
            'Json' => json_encode($data),
        ];

        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($apiData);
        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error adding transactional data to a contact')
                ->addClientLog('Failed transactional data', [
                    'collection' => $collectionName,
                    'data' => $apiData,
                ], Logger::DEBUG);
        }

        return $response;
    }

    /**
     * Adds a single piece of transactional data to account.
     *
     * @param array $data
     * @param string $collectionName
     *
     * @return null
     * @throws \Exception
     */
    public function postAccountTransactionalData(
        $data,
        $collectionName
    ) {
        $item = $this->getContactsTransactionalDataByKey(
            $collectionName,
            $data['id']
        );
        if (!isset($item->key) || isset($item->message)
            && $item->message == self::API_ERROR_TRANS_NOT_EXISTS
        ) {
            $url = $this->getApiEndpoint() . self::REST_TRANSACTIONAL_DATA
                . $collectionName;
        } else {
            $url = $this->getApiEndpoint() . self::REST_TRANSACTIONAL_DATA
                . $collectionName . '/' . $item->key;
        }
        $apiData = [
            'Key' => $data['id'],
            'ContactIdentifier' => 'account',
            'Json' => json_encode($data),
        ];

        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($apiData);
        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error adding transactional data to account')
                ->addClientLog('Failed transactional data', [
                    'collection' => $collectionName,
                    'data' => $apiData,
                ], Logger::DEBUG);
        }

        return $response;
    }

    /**
     * Gets a piece of transactional data by key.
     *
     * @param string $name
     * @param int $key
     *
     * @return null
     * @throws \Exception
     */
    public function getContactsTransactionalDataByKey($name, $key)
    {
        $url = $this->getApiEndpoint() . self::REST_TRANSACTIONAL_DATA . $name . '/'
            . $key;
        $this->setUrl($url)
            ->setVerb('GET');

        return $this->execute();
    }

    /**
     * Deletes all transactional data for a contact.
     *
     * @param string $email
     * @param string $collectionName
     *
     * @return null
     * @throws \Exception
     */
    public function deleteContactTransactionalData(
        $email,
        $collectionName = 'Orders'
    ) {
        if ($email && $collectionName) {
            $url = $this->getApiEndpoint() . '/v2/contacts/' . $email
                . '/transactional-data/' . $collectionName;
            $this->setUrl($url)
                ->setVerb('DELETE');

            return $this->execute();
        }
    }

    /**
     * Gets a summary of information about the current status of the account.
     *
     * @return object
     *
     * @throws \Exception
     */
    public function getAccountInfo()
    {
        $url = self::REST_ACCOUNT_INFO;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();
        if (isset($response->message)) {
            $this->addClientLog('Error getting account info for API user');
        }

        return $response;
    }

    /**
     * Resubscribes a previously unsubscribed contact.
     *
     * @param array $apiContact
     *
     * @return object
     *
     * @throws \Exception
     */
    public function postContactsResubscribe($apiContact)
    {
        $url = $this->getApiEndpoint() . self::REST_CONTACTS_RESUBSCRIBE;
        $data = [
            'UnsubscribedContact' => $apiContact,
        ];
        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($data);

        $response = $this->execute();
        if (isset($response->message)) {
            $this->addClientLog('Error resubscribing contact')
                ->addClientLog('Failed contact resubscription', [
                    'contact' => $apiContact,
                ], Logger::DEBUG);
        }

        return $response;
    }

    /**
     * Gets all custom from addresses which can be used in a campaign.
     *
     * @return object
     *
     * @throws \Exception
     */
    public function getCustomFromAddresses()
    {
        $url = $this->getApiEndpoint() . self::REST_CAMPAIGN_FROM_ADDRESS_LIST;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error getting custom from addresses');
        }

        return $response;
    }

    /**
     * Creates a campaign.
     *
     * @param array $data
     *
     * @return null
     * @throws \Exception
     */
    public function postCampaign($data)
    {
        $url = $this->getApiEndpoint() . self::REST_CREATE_CAMPAIGN;
        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($data);

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error creating campaign')
                ->addClientLog('Failed campaign data', [
                    'data' => $data,
                ], Logger::DEBUG);
        }

        return $response;
    }

    /**
     * https://apiconnector.com/v2/programs?select={select}&skip={skip}.
     * @param int $skip
     * @param int $select
     * @return mixed|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPrograms($skip = 0, $select = 1000)
    {
        $url = sprintf(
            '%s%s?select=%s&skip=%s',
            $this->getApiEndpoint(),
            self::REST_PROGRAM,
            $select,
            $skip
        );

        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error getting programs');
        }

        return $response;
    }

    /**
     * Creates an enrolment.
     *
     * @param array $data
     *
     * @return null
     * @throws \Exception
     */
    public function postProgramsEnrolments($data)
    {
        $url = $this->getApiEndpoint() . self::REST_PROGRAM_ENROLMENTS;
        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($data);

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error sending program enrolments')
                ->addClientLog('Failed program enrolments data', [
                    'data' => $data,
                ], Logger::DEBUG);
        }

        return $response;
    }

    /**
     * Gets a program by id.
     *
     * @param int $id
     *
     * @return null
     * @throws \Exception
     */
    public function getProgramById($id)
    {
        $url = $this->getApiEndpoint() . self::REST_PROGRAM . $id;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();
        if (isset($response->message)) {
            $this->addClientLog('Error getting program by ID', [
                'program_id' => $id,
            ]);
        }

        return $response;
    }

    /**
     * Gets a summary of reporting information for a specified campaign.
     *
     * @param int $campaignId
     *
     * @return null
     * @throws \Exception
     */
    public function getCampaignSummary($campaignId)
    {
        $url = $this->getApiEndpoint() . '/v2/campaigns/' . $campaignId
            . '/summary';
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error getting campaign summary', [
                'campaign_id' => $campaignId,
            ]);
        }

        return $response;
    }

    /**
     * Deletes a piece of transactional data by key.
     *
     * @param int  $key
     * @param string $collectionName
     *
     * @return null
     * @throws \Exception
     */
    public function deleteContactsTransactionalData(
        $key,
        $collectionName = 'Orders'
    ) {
        if ($key && $collectionName) {
            $url = $this->getApiEndpoint() . '/v2/contacts/transactional-data/'
                . $collectionName . '/' . $key;
            $this->setUrl($url)
                ->setVerb('DELETE');

            $response = $this->execute();

            if (isset($response->message)) {
                $this->addClientLog('Error deleting transactional data for contacts');
            }

            return $response;
        }
    }

    /**
     * Adds a document to a campaign as an attachment.
     *
     * @param int $campaignId
     * @param array $data
     *
     * @return null
     * @throws \Exception
     */
    public function postCampaignAttachments($campaignId, $data)
    {
        $url = $this->getApiEndpoint() . self::REST_CREATE_CAMPAIGN
            . "/$campaignId/attachments";
        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($data);

        $result = $this->execute();

        if (isset($result->message)) {
            $this->addClientLog('Error adding campaign attachments');
        }

        return $result;
    }

    /**
     * Get contact address books.
     *
     * @param int $contactId
     *
     * @return object
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getContactAddressBooks($contactId)
    {
        $url = $this->getApiEndpoint() . '/v2/contacts/' . $contactId
            . '/address-books';
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error fetching address books for contact', [
                'contact_id' => $contactId,
            ]);
        }

        return $response;
    }

    /**
     * Gets list of all templates.
     *
     * @return object
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getApiTemplateList()
    {
        $url = $this->getApiEndpoint() . self::REST_TEMPLATES;
        $this->setUrl($url)
            ->setVerb('GET');
        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error getting template list');
        }

        return $response;
    }

    /**
     * Gets a template by ID.
     *
     * @param string $templateId
     *
     * @return object
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getApiTemplate($templateId)
    {
        $url = $this->getApiEndpoint() . self::REST_TEMPLATES . '/' . $templateId;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error getting template by ID', [
                'template_id' => $templateId
            ]);
        }

        return $response;
    }

    /**
     * Adds multiple pieces of transactional data to account asynchronously,
     * returning an identifier that can be used to check for import progress.
     *
     * @param  array $transactionalData
     * @param string $collectionName
     *
     * @return null
     * @throws \Exception
     */
    public function postAccountTransactionalDataImport(
        $transactionalData,
        $collectionName = 'Catalog_Default'
    ) {
        $orders = [];
        foreach ($transactionalData as $one) {
            if (isset($one['id'])) {
                $orders[] = [
                    'Key' => $one['id'],
                    'ContactIdentifier' => 'account',
                    'Json' => json_encode($one),
                ];
            }
        }
        $url = $this->getApiEndpoint() . self::REST_TRANSACTIONAL_DATA_IMPORT
            . $collectionName;
        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($orders);

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error in bulk import of transactional data for account')
                ->addClientLog('Failed transactional data', [
                    'collection' => $collectionName,
                    'data' => $orders
                ], Logger::DEBUG);
        }

        return $response;
    }

    /**
     * Send integration insight data
     *
     * @param array $insightData
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function postIntegrationInsightData(array $insightData)
    {
        $response = $this->setUrl($this->getApiEndpoint() . self::REST_TRANSACTIONAL_DATA_IMPORT . 'Integrations')
            ->setVerb('POST')
            ->buildPostBody([[
                'Key' => $insightData['recordId'],
                'ContactIdentifier' => 'account',
                'Json' => json_encode($insightData),
            ]])
            ->execute();

        if (!$response || isset($response->message)) {
            $this->addClientLog('Error sending integration insight data');
            return false;
        }

        return true;
    }

    /**
     * Gets the import status of a previously started contact import.
     *
     * @param string $importId
     *
     * @return null
     * @throws \Exception
     */
    public function getContactsImportByImportId($importId)
    {
        $url = $this->getApiEndpoint() . self::REST_CONTACTS_IMPORT . $importId;

        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error getting contacts import status', [
                'import_id' => $importId
            ]);
        }

        return $response;
    }

    /**
     * Gets the import status of a previously started transactional import.
     *
     * @param string $importId
     *
     * @return object
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getContactsTransactionalDataImportByImportId($importId)
    {
        $url = $this->getApiEndpoint() . self::REST_TRANSACTIONAL_DATA_IMPORT
            . $importId;

        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error getting transactional data import status', [
                'import_id' => $importId
            ]);
        }

        return $response;
    }

    /**
     * Get contact import report faults.
     *
     * @param string $id
     *
     * @return bool|null
     *
     * @throws \Exception
     */
    public function getContactImportReportFaults($id)
    {
        $this->isNotJson = true;
        $url = $this->getApiEndpoint() . self::REST_CONTACTS_IMPORT . $id . '/report-faults';
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();
        $this->isNotJson = false;

        //if string is JSON than there is a error message
        if (json_decode($response)) {
            //log error
            if (isset($response->message)) {
                $this->addClientLog('Error getting contacts import report faults', [
                    'import_id' => $id
                ]);
            }

            return false;
        }

        return $response;
    }

    /**
     * Gets the send status using send ID.
     *
     * @param string $id
     * @return object
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSendStatus($id)
    {
        $url = $this->getApiEndpoint() . self::REST_CAMPAIGN_SEND . '/' . $id;
        $this->setUrl($url)
            ->setVerb('GET');
        $response = $this->execute();
        //log error
        if (isset($response->message)
            && !in_array(
                $response->message,
                $this->excludeMessages
            )
        ) {
            $this->addClientLog('Error getting send status', [
                'send_id' => $id
            ]);
        }
        return $response;
    }

    /**
     * Get access token.
     *
     * @param string $url
     * @param array|string $params
     *
     * @return string/object
     */
    public function getAccessToken($url, $params)
    {
        // @codingStandardsIgnoreStart
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $response = json_decode(curl_exec($ch));
        // @codingStandardsIgnoreEnd

        if ($response === false) {
            // @codingStandardsIgnoreLine
            $this->helper->error('Error Number: ' . curl_errno($ch), []);
        } elseif (isset($response->error)) {
            $this->helper->error('OAUTH failed. Error - ' . $response->error, []);
            if (isset($response->error_description)) {
                $this->helper->error('OAUTH failed. Error description - ' . $response->error_description, []);
            }
        } elseif (isset($response->access_token)) {
            return $response->access_token;
        }

        return $response;
    }

    /**
     * Sends a transactional email.
     *
     * @param array $content
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sendApiTransactionalEmail($content)
    {
        $url = $this->getApiEndpoint() . self::REST_SEND_TRANSACTIONAL_EMAIL;

        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($content);

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error sending transactional email');
        }

        return $response;
    }

    /**
     * Gets transactional email reporting statistics for a specified time period.
     *
     * @param string $date
     * @param null $endDate
     * @param null $aggregatedBy 'AllTime', 'Month', 'Week', 'Day'
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getEmailStats($date, $endDate = null, $aggregatedBy = null)
    {
        $url = $this->getApiEndpoint() . '/v2/email/stats/since-date/' . $date;
        if ($endDate && $aggregatedBy) {
            $url .= '?endDate=' . $endDate . '&aggregatedBy=' . $aggregatedBy;
        }

        $response = $this->setUrl($url)
            ->setVerb('GET')
            ->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error getting email stats');
        }

        return $response;
    }

    /**
     * Gets all preferences that a given contact is opted into
     *
     * @param $contactId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPreferencesForContact($contactId)
    {
        $url = $this->getApiEndpoint() . "/v2/contact/" . $contactId . "/preferences";

        $response = $this->setUrl($url)
            ->setVerb('GET')
            ->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error getting preferences for contact', [
                'contact_id' => $contactId
            ]);
        }

        return $response;
    }

    /**
     * Opts in a given contact to preferences, or opts out a given contact from preferences
     *
     * @param $contactId
     * @param $preferences
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setPreferencesForContact($contactId, $preferences)
    {
        $url = $this->getApiEndpoint() . "/v2/contact/" . $contactId . "/preferences";
        $this->setUrl($url)
            ->setVerb('PUT')
            ->buildPostBody($preferences);

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error setting preferences for contact', [
                'contact_id' => $contactId
            ]);
        }

        return $response;
    }

    /**
     * Create contact with consent
     *
     * @param array $contact
     * @param array $consentFields
     *
     * @return mixed
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function postContactWithConsent($contact, $consentFields)
    {
        $url = $this->getApiEndpoint() . self::REST_CONTACT_WITH_CONSENT;
        $data = [
            'Contact' => $contact,
            'ConsentFields' => [['fields' => $consentFields]]
        ];
        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($data);
        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error creating contact with consent')
                ->addClientLog('Failed contact data', [
                    'data' => $data,
                ], Logger::DEBUG);
            return $response;
        }

        return $response->contact;
    }

    /**
     * Gets the preferences, as a tree structure
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPreferences()
    {
        $url = $this->getApiEndpoint() . "/v2/preferences";

        $response = $this->setUrl($url)
            ->setVerb('GET')
            ->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error getting preferences');
        }

        return $response;
    }

    /**
     * Create contact with consent
     *
     * @param array $contact
     * @param array $consentFields
     * @param array $preferences
     *
     * @return mixed
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function postContactWithConsentAndPreferences($contact, $consentFields, $preferences)
    {
        $url = $this->getApiEndpoint() . self::REST_CONTACT_WITH_CONSENT_AND_PREFERENCES;
        $data = [
            'Contact' => $contact,
            'ConsentFields' => [['fields' => $consentFields]],
            'Preferences' => [$preferences]
        ];
        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($data);
        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error creating contact with consent and preferences')
                ->addClientLog('Failed contact data', [
                    'data' => $data,
                ], Logger::DEBUG);
        }

        return $response;
    }

    /**
     * Setup New Chat Account
     * @param $data
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setUpChatAccount(array $data = [])
    {
        $url = $this->getApiEndpoint() . self::REST_CHAT_SETUP;

        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($data);

        $response = $this->execute();

        return $response;
    }

    /**
     * Resubscribes a previously unsubscribed contact to a given address book
     *
     * @param int $addressBookId
     * @param string $email
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function postAddressBookContactResubscribe($addressBookId, $email)
    {
        $contact = ['unsubscribedContact' => ['email' => $email]];
        $url = $this->getApiEndpoint() . self::REST_ADDRESS_BOOKS . $addressBookId
            . '/contacts/resubscribe';
        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($contact);

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('Error resubscribing address book contact');
        }

        return $response;
    }

    /**
     * Get list of all surveys and forms.
     *
     * @param string $assignedToAddressBookOnly
     * @param int $select
     * @param int $skip
     * @return mixed
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSurveysAndForms($assignedToAddressBookOnly = 'false', $select = 1000, $skip = 0)
    {
        $url = sprintf(
            '%s%s?assignedToAddressBookOnly=%s&select=%s&skip=%s',
            $this->getApiEndpoint(),
            self::REST_SURVEYS_FORMS,
            $assignedToAddressBookOnly,
            $select,
            $skip
        );

        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();

        if (empty($response) || isset($response->message)) {
            $this->addClientLog('Error getting surveys and forms');
        }

        return $response;
    }

    public function getFormById($formId)
    {
        $url = sprintf(
            '%s%s/%s',
            $this->getApiEndpoint(),
            self::REST_SURVEYS_FORMS,
            $formId
        );

        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();

        if (empty($response) || isset($response->message)) {
            $this->addClientLog('Error getting data for form id ' . $formId);
        }

        return $response;
    }
}
