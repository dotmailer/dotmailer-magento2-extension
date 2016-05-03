<?php

namespace Dotdigitalgroup\Email\Model\Sync\Contact;

use DotMailer\Api\DataTypes\ApiContact;
use DotMailer\Api\DataTypes\ApiFileMedia;
use DotMailer\Api\DataTypes\ApiTransactionalDataList;
use DotMailer\Api\DataTypes\ApiTransactionalData;
use Symfony\Component\Config\Definition\Exception\Exception;

class Bulk
{

    protected $_helper;
    protected $_client;
    protected $_fileHelper;

    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Helper\File $fileHelper,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
    ) {
        $this->_helper     = $helper;
        $this->_fileHelper = $fileHelper;
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


                $filePath               = $this->_fileHelper->getFilePath(
                    $file
                );
                $apiFileMedia           = new ApiFileMedia();
                $apiFileMedia->fileName = pathinfo($file, PATHINFO_FILENAME);
                $apiFileMedia->data     = base64_encode(
                    file_get_contents($filePath)
                );
                //import contacts from csv file
                $result = $this->_client->PostAddressBookContactsImport(
                    $addressBook, $apiFileMedia
                );

                $this->_handleItemAfterSync($item, $result, $file);
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

    protected function _handleItemAfterSync($item, $result, $file = false)
    {
        $curlError = $this->_checkCurlError($item);

        if ( ! $curlError) {

            if (! $result->id) {
                $message = 'Error unknown';

                $item->setImportStatus(
                    \Dotdigitalgroup\Email\Model\Importer::FAILED
                )
                    ->setMessage($message);

                $item->save();
            } elseif ($result->id) {

                if ($file) {
                    $this->_fileHelper->archiveCSV($file);
                }

                $item->setImportStatus(
                    \Dotdigitalgroup\Email\Model\Importer::IMPORTING
                )
                    ->setImportId($result->id)
                    ->setImportStarted(time())
                    ->setMessage('')
                    ->save();
            } else {
                $message = 'Error unknown';
                $item->setImportStatus(
                    \Dotdigitalgroup\Email\Model\Importer::FAILED
                )
                    ->setMessage($message);

                //If result id
                if (isset($result->id)) {
                    $item->setImportId($result->id);
                }

                $item->save();
            }
        }
    }

    protected function _checkCurlError($item)
    {
        return false;
        
        $curlError = $this->_client->getCurlError();
        if ($curlError) {
            $item->setMessage($curlError)
                ->setImportStatus(\Dotdigitalgroup\Email\Model\Importer::FAILED)
                ->save();

            return true;
        }

        return false;
    }
}