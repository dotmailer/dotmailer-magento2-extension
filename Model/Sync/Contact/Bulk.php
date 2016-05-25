<?php

namespace Dotdigitalgroup\Email\Model\Sync\Contact;

class Bulk
{

    protected $_helper;
    protected $_client;
    protected $_contactFactory;

    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
    ) {
        $this->_helper     = $helper;
        $this->_contactFactory = $contactFactory;
    }

    public function sync($collection)
    {
        foreach ($collection as $item) {

            $websiteId     = $item->getWebsiteId();
            $file          = $item->getImportFile();
            $this->_client = $this->_helper->getWebsiteApiClient($websiteId);

            $addressBook = $this->_getAddressBook(
                $item->getImportType(), $websiteId
            );

            if ( ! empty($file) && ! empty($addressBook) && $this->_client) {

                //import contacts from csv file
                $result = $this->_client->postAddressBookContactsImport(
                    $file, $addressBook
                );

                $this->_handleItemAfterSync($item, $result);
            }
        }
    }

    /**
     * Get addressbook by import type.
     *
     * @param $importType
     * @param $websiteId
     *
     * @return mixed|string
     */
    protected function _getAddressBook($importType, $websiteId)
    {
        switch ($importType) {
            case \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_CONTACT :
                $addressBook = $this->_helper->getCustomerAddressBook(
                    $websiteId
                );
                break;
            case \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_SUBSCRIBERS:
                $addressBook = $this->_helper->getSubscriberAddressBook(
                    $websiteId
                );
                break;
            case \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_GUEST:
                $addressBook = $this->_helper->getGuestAddressBook($websiteId);
                break;
            default :
                $addressBook = '';
        }

        return $addressBook;
    }

    protected function _handleItemAfterSync($item, $result)
    {
        if (isset($result->message) && !isset($result->id)) {
            $item->setImportStatus(\Dotdigitalgroup\Email\Model\Importer::FAILED)
                ->setMessage($result->message)
                ->save();
        } elseif (isset($result->id) && !isset($result->message)) {
            $item->setImportStatus(\Dotdigitalgroup\Email\Model\Importer::IMPORTING)
                ->setImportId($result->id)
                ->setImportStarted(time())
                ->setMessage('')
                ->save();
        } else {
            $message = (isset($result->message)) ? $result->message : 'Error unknown';
            $item->setImportStatus(\Dotdigitalgroup\Email\Model\Importer::FAILED)
                ->setMessage($message);

            //If result id
            if (isset($result->id)) {
                $item->setImportId($result->id);
            }

            $item->save();
        }
    }
}