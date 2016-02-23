<?php

namespace Dotdigitalgroup\Email\Model;


class Proccessor
{

    //import statuses
    const NOT_IMPORTED = 0;
    const IMPORTING = 1;
    const IMPORTED = 2;
    const FAILED = 3;

    //import mode
    const MODE_BULK = 'Bulk';
    const MODE_SINGLE = 'Single';
    const MODE_SINGLE_DELETE = 'Single_Delete';
    const MODE_CONTACT_DELETE = 'Contact_Delete';
    const MODE_CONTACT_EMAIL_UPDATE = 'Contact_Email_Update';
    const MODE_SUBSCRIBER_UPDATE = 'Subscriber_Update';
    const MODE_SUBSCRIBER_RESUBSCRIBED = 'Subscriber_Resubscribed';

    //import type
    const IMPORT_TYPE_GUEST = 'Guest';
    const IMPORT_TYPE_QUOTE = 'Quote';
    const IMPORT_TYPE_ORDERS = 'Orders';
    const IMPORT_TYPE_REVIEWS = 'Reviews';
    const IMPORT_TYPE_CONTACT = 'Contact';
    const IMPORT_TYPE_WISHLIST = 'Wishlist';
    const IMPORT_TYPE_SUBSCRIBERS = 'Subscriber';
    const IMPORT_TYPE_CATALOG = 'Catalog_Default';
    const IMPORT_TYPE_CONTACT_UPDATE = 'Contact';
    const IMPORT_TYPE_SUBSCRIBER_UPDATE = 'Subscriber';
    const IMPORT_TYPE_SUBSCRIBER_RESUBSCRIBED = 'Subscriber';

    protected $import_statuses
        = array(
            'RejectedByWatchdog',
            'InvalidFileFormat',
            'Unknown',
            'Failed',
            'ExceedsAllowedContactLimit',
            'NotAvailableInThisVersion'
        );

    protected $_reasons
        = array(
            'Globally Suppressed',
            'Blocked',
            'Unsubscribed',
            'Hard Bounced',
            'Isp Complaints',
            'Domain Suppressed',
            'Failures',
            'Invalid Entries',
            'Mail Blocked',
            'Suppressed by you'
        );

    protected $_helper;
    protected $_fileHelper;
    protected $_importerFactory;
    protected $_directoryList;
    protected $_file;
    protected $_contact;
    protected $_contactModel;


    /**
     * Proccessor constructor.
     *
     * @param ImporterFactory                                 $importerFactory
     * @param \Dotdigitalgroup\Email\Helper\Data              $helper
     * @param \Dotdigitalgroup\Email\Helper\File              $fileHelper
     * @param Resource\Importer\CollectionFactory             $importerCollectionFactory
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Io\File           $file
     * @param Resource\Contact                                $contact
     * @param Contact                                         $contactModel
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Helper\File $fileHelper,
        \Dotdigitalgroup\Email\Model\Resource\Importer\CollectionFactory $importerCollectionFactory,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Io\File $file,
        \Dotdigitalgroup\Email\Model\Resource\Contact $contact,
        \Dotdigitalgroup\Email\Model\Contact $contactModel
    ) {
        $this->_importerFactory   = $importerFactory;
        $this->_helper            = $helper;
        $this->_fileHelper        = $fileHelper;
        $this->importerCollection = $importerCollectionFactory->create();
        $this->_directoryList     = $directoryList;
        $this->_file              = $file;
        $this->_contact           = $contact;
        $this->_contactModel      = $contactModel;
    }

    /**
     * register import in queue.
     *
     * @param            $importType
     * @param            $importData
     * @param            $importMode
     * @param            $websiteId
     * @param bool|false $file
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function registerQueue(
        $importType,
        $importData,
        $importMode,
        $websiteId,
        $file = false
    ) {
        try {
            $importModel = $this->_importerFactory->create();
            if ( ! empty($importData)) {
                $importData = serialize($importData);
            }
            //filename to be imported
            if ($file) {
                $importModel->setImportFile($file);
            }

            //save import data
            $importModel->setImportType($importType)
                ->setImportData($importData)
                ->setWebsiteId($websiteId)
                ->setImportMode($importMode)
                ->save();

        } catch (\Exception $e) {
            $this->_helper->debug((string)$e, array());
        }
    }

    /**
     * start point. importer queue processor. check if un-finished import exist.
     *
     * @return bool
     */
    public function processQueue()
    {
        $this->_helper->allowResourceFullExecution();

        if ($item = $this->_getQueue(true)) {
            $websiteId = $item->getWebsiteId();
            if ($this->_helper->isEnabled($websiteId)) {
                $client = $this->_helper->getWebsiteApiClient($websiteId);
                if (
                    $item->getImportType() == self::IMPORT_TYPE_CONTACT or
                    $item->getImportType() == self::IMPORT_TYPE_SUBSCRIBERS or
                    $item->getImportType() == self::IMPORT_TYPE_GUEST

                ) {
                    $response = $client->getContactsImportByImportId(
                        $item->getImportId()
                    );
                } else {
                    $response
                        = $client->getContactsTransactionalDataImportByImportId(
                        $item->getImportId()
                    );
                }

                if ($response && ! isset($response->message)) {
                    if ($response->status == 'Finished') {
                        $now = gmDate('Y-m-d H:i:s');
                        $item->setImportStatus(self::IMPORTED)
                            ->setImportFinished($now)
                            ->setMessage('')
                            ->save();

                        if (
                            $item->getImportType() == self::IMPORT_TYPE_CONTACT
                            or
                            $item->getImportType()
                            == self::IMPORT_TYPE_SUBSCRIBERS or
                            $item->getImportType() == self::IMPORT_TYPE_GUEST

                        ) {
                            if ($item->getImportId()) {
                                $this->_processContactImportReportFaults(
                                    $item->getImportId(), $websiteId
                                );
                            }
                        }

                        $this->_processQueue();

                    } elseif (in_array(
                        $response->status, $this->import_statuses
                    )) {
                        $item->setImportStatus(self::FAILED)
                            ->setMessage($response->status)
                            ->save();

                        $this->_processQueue();
                    }
                }
                if ($response && isset($response->message)) {
                    $item->setImportStatus(self::FAILED)
                        ->setMessage($response->message)
                        ->save();

                    $this->_processQueue();
                }
            }
        } else {
            $this->_processQueue();
        }

        return true;
    }

    protected function _processContactImportReportFaults($id, $websiteId)
    {
        $client = $this->_helper->getWebsiteApiClient($websiteId);
        $data   = $client->getContactImportReportFaults($id);

        if ($data) {
            $data     = $this->_remove_utf8_bom($data);
            $fileName = $this->_directoryList->getPath('var')
                . DIRECTORY_SEPARATOR . 'DmTempCsvFromApi.csv';
            $this->_file->open();
            $check = $this->_file->write($fileName, $data);
            if ($check) {
                $csvArray = $this->_csv_to_array($fileName);
                $this->_file->rm($fileName);
                $this->_contact->unsubscribe($csvArray);
            } else {
                $this->_helper->log(
                    '_processContactImportReportFaults: cannot save data to CSV file.'
                );
            }
        }
    }

    /**
     * actual importer queue processor
     */
    protected function _processQueue()
    {
        //item in queue
        if ($item = $this->_getQueue()) {
            $websiteId = $item->getWebsiteId();

            /** @var \Dotdigitalgroup\Email\Model\Apiconnector\Client $client */
            $client = $this->_helper->getWebsiteApiClient($websiteId);

            $now   = gmdate('Y-m-d H:i:s');
            $error = false;

            if ( //import requires file
                $item->getImportType() == self::IMPORT_TYPE_CONTACT or
                $item->getImportType() == self::IMPORT_TYPE_SUBSCRIBERS or
                $item->getImportType() == self::IMPORT_TYPE_GUEST
            ) {
                if ($item->getImportMode() == self::MODE_CONTACT_DELETE) {
                    //remove from account
                    $client     = $this->_helper->getWebsiteApiClient(
                        $websiteId
                    );
                    $email      = unserialize($item->getImportData());
                    $apiContact = $client->postContacts($email);
                    if ( ! isset($apiContact->message)
                        && isset($apiContact->id)
                    ) {
                        $result = $client->deleteContact($apiContact->id);
                        if (isset($result->message)) {
                            $error = true;
                        }
                    } elseif (isset($apiContact->message)
                        && ! isset($apiContact->id)
                    ) {
                        $error  = true;
                        $result = $apiContact;
                    }
                } else {
                    //address book
                    $addressbook = '';
                    if ($item->getImportType() == self::IMPORT_TYPE_CONTACT) {
                        $addressbook = $this->_helper->getCustomerAddressBook(
                            $websiteId
                        );
                    }
                    if ($item->getImportType()
                        == self::IMPORT_TYPE_SUBSCRIBERS
                    ) {
                        $addressbook = $this->_helper->getSubscriberAddressBook(
                            $websiteId
                        );
                    }
                    if ($item->getImportType() == self::IMPORT_TYPE_GUEST) {
                        $addressbook = $this->_helper->getGuestAddressBook(
                            $websiteId
                        );
                    }

                    $file = $item->getImportFile();
                    if ( ! empty($file) && ! empty($addressbook)) {
                        $result = $client->postAddressBookContactsImport(
                            $file, $addressbook
                        );

                        if (isset($result->message) && ! isset($result->id)) {
                            $error = true;
                        }
                    }
                }
            } elseif ($item->getImportMode()
                == self::MODE_SINGLE_DELETE
            ) { //import to single delete
                $importData = unserialize($item->getImportData());
                $result     = $client->deleteContactsTransactionalData(
                    $importData[0], $item->getImportType()
                );
                if (isset($result->message)) {
                    $error = true;
                }
            } else {
                $importData = unserialize($item->getImportData());
                //catalog type and bulk mode
                if (strpos($item->getImportType(), 'Catalog_') !== false
                    && $item->getImportMode() == self::MODE_BULK
                ) {
                    $result = $client->postAccountTransactionalDataImport(
                        $importData, $item->getImportType()
                    );
                    if (isset($result->message) && ! isset($result->id)) {
                        $error = true;
                    }
                } elseif ($item->getImportMode()
                    == self::MODE_SINGLE
                ) { // single contact import
                    $result = $client->postContactsTransactionalData(
                        $importData, $item->getImportType()
                    );
                    if (isset($result->message)) {
                        $error = true;
                    }
                } elseif ($item->getImportMode()
                    == self::MODE_CONTACT_EMAIL_UPDATE
                ) {
                    $emailBefore            = $importData['emailBefore'];
                    $email                  = $importData['email'];
                    $isSubscribed           = $importData['isSubscribed'];
                    $subscribersAddressBook = $this->_helper->getWebsiteConfig(
                        \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID,
                        $websiteId
                    );
                    $result                 = $client->postContacts(
                        $emailBefore
                    );
                    //check for matching email
                    if (isset($result->id)) {
                        if ($email != $result->email) {
                            $data = array(
                                'Email'     => $email,
                                'EmailType' => 'Html'
                            );
                            //update the contact with same id - different email
                            $client->updateContact($result->id, $data);
                        }
                        if ( ! $isSubscribed
                            && $result->status == 'Subscribed'
                        ) {
                            $client->deleteAddressBookContact(
                                $subscribersAddressBook, $result->id
                            );
                        }
                    } elseif (isset($result->message)) {
                        $error = true;
                    }
                } elseif ($item->getImportMode()
                    == self::MODE_SUBSCRIBER_UPDATE
                ) {
                    $email        = $importData['email'];
                    $id           = $importData['id'];
                    $contactEmail = $this->_contactModel->load($id);
                    $result       = $client->postContacts($email);
                    if (isset($result->id)) {
                        $contactId = $result->id;
                        $client->deleteAddressBookContact(
                            $this->_helper->getSubscriberAddressBook(
                                $websiteId
                            ), $contactId
                        );
                        $contactEmail->setContactId($contactId)
                            ->save();
                    } else {
                        $contactEmail->setSuppressed('1')
                            ->save();
                    }
                } elseif ($item->getImportMode()
                    == self::MODE_SUBSCRIBER_RESUBSCRIBED
                ) {
                    $email      = $importData['email'];
                    $apiContact = $client->postContacts($email);

                    //resubscribe suppressed contacts
                    if (isset($apiContact->message)
                        && $apiContact->message
                        == \Dotdigitalgroup\Email\Model\Apiconnector\Client::API_ERROR_CONTACT_SUPPRESSED
                    ) {
                        $apiContact = $client->getContactByEmail($email);
                        $client->postContactsResubscribe($apiContact);
                    }
                } else { //bulk import transactional data
                    $result = $client->postContactsTransactionalDataImport(
                        $importData, $item->getImportType()
                    );
                    if (isset($result->message) && ! isset($result->id)) {
                        $error = true;
                    }
                }
            }
            if ( ! $error) {
                if ($item->getImportMode() == self::MODE_SINGLE_DELETE or
                    $item->getImportMode() == self::MODE_SINGLE or
                    $item->getImportMode() == self::MODE_CONTACT_DELETE or
                    $item->getImportMode() == self::MODE_CONTACT_EMAIL_UPDATE or
                    $item->getImportMode() == self::MODE_SUBSCRIBER_RESUBSCRIBED
                    or
                    $item->getImportMode() == self::MODE_SUBSCRIBER_UPDATE
                ) {
                    $item->setImportStatus(self::IMPORTED)
                        ->setImportFinished($now)
                        ->setImportStarted($now)
                        ->save();
                } elseif (isset($result->id)) {
                    $item->setImportStatus(self::IMPORTING)
                        ->setImportId($result->id)
                        ->setImportStarted($now)
                        ->save();
                } else {
                    $item->setImportStatus(self::FAILED)
                        ->setMessage($result->message)
                        ->save();
                }
            } elseif ($error) {
                $item->setImportStatus(self::FAILED)
                    ->setMessage($result->message)
                    ->save();
            }
        }
    }

    /**
     * get queue items from importer.
     *
     * @param bool|false $importing
     *
     * @return bool
     */
    protected function _getQueue($importing = false)
    {
        //reset collection, using same collection multiple times before load.
        $this->importerCollection->reset();
        //if true then return item with importing status
        if ($importing) {
            $this->importerCollection->addFieldToFilter(
                'import_status', array('eq' => self::IMPORTING)
            );
        } else {
            $this->importerCollection->addFieldToFilter(
                'import_status', array('eq' => self::NOT_IMPORTED)
            );
        }

        $this->importerCollection->setPageSize(1);

        if ($this->importerCollection->getSize()) {
            return $this->importerCollection->getFirstItem();
        }

        return false;
    }

    protected function _csv_to_array($filename)
    {
        if ( ! file_exists($filename) || ! is_readable($filename)) {
            return false;
        }

        $header = null;
        $data   = array();
        if (($handle = fopen($filename, 'r')) !== false) {
            while (($row = fgetcsv($handle)) !== false) {
                if ( ! $header) {
                    $header = $row;
                } else {
                    $data[] = array_combine($header, $row);
                }
            }
            fclose($handle);
        }

        $contacts = array();
        foreach ($data as $item) {
            if (in_array($item['Reason'], $this->_reasons)) {
                $contacts[] = $item['email'];
            }
        }

        return $contacts;
    }

    protected function _remove_utf8_bom($text)
    {
        $bom  = pack('H*', 'EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);

        return $text;
    }
}