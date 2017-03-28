<?php

namespace Dotdigitalgroup\Email\Model\Sync\Contact;

class Bulk
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var
     */
    public $client;
    /**
     * @var \Dotdigitalgroup\Email\Helper\File
     */
    public $fileHelper;
    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    public $contactFactory;

    /**
     * Bulk constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Helper\File $fileHelper
     * @param \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Helper\File $fileHelper,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
    ) {
        $this->helper         = $helper;
        $this->fileHelper     = $fileHelper;
        $this->contactFactory = $contactFactory;
    }

    /**
     * Sync.
     *
     * @param $collection
     */
    public function sync($collection)
    {
        foreach ($collection as $item) {
            $websiteId = $item->getWebsiteId();
            $file = $item->getImportFile();
            if ($this->helper->isEnabled($websiteId)) {
                $this->client = $this->helper->getWebsiteApiClient($websiteId);

                $addressBook = $this->_getAddressBook(
                    $item->getImportType(),
                    $websiteId
                );

                if (!empty($file) && !empty($addressBook) && $this->client) {
                    //import contacts from csv file
                    $result = $this->client->postAddressBookContactsImport(
                        $file,
                        $addressBook
                    );

                    $this->_handleItemAfterSync($item, $result);
                }
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
    public function _getAddressBook($importType, $websiteId)
    {
        switch ($importType) {
            case \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_CONTACT:
                $addressBook = $this->helper->getCustomerAddressBook(
                    $websiteId
                );
                break;
            case \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_SUBSCRIBERS:
                $addressBook = $this->helper->getSubscriberAddressBook(
                    $websiteId
                );
                break;
            case \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_GUEST:
                $addressBook = $this->helper->getGuestAddressBook($websiteId);
                break;
            default:
                $addressBook = '';
        }

        return $addressBook;
    }

    /**
     * @param $item
     * @param $result
     */
    public function _handleItemAfterSync($item, $result)
    {
        $curlError = $this->_checkCurlError($item);

        if (!$curlError) {
            if (isset($result->message) && !isset($result->id)) {
                $item->setImportStatus(\Dotdigitalgroup\Email\Model\Importer::FAILED)
                    ->setMessage($result->message);

                $item->save();
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

    /**
     * @param $item
     *
     * @return bool
     */
    public function _checkCurlError($item)
    {
        //if curl error 28
        $curlError = $this->client->getCurlError();
        if ($curlError) {
            $item->setMessage($curlError)
                ->setImportStatus(\Dotdigitalgroup\Email\Model\Importer::FAILED)
                ->save();

            return true;
        }

        return false;
    }
}
