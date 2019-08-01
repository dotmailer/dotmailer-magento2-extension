<?php

namespace Dotdigitalgroup\Email\Model\Sync\Contact;

use \Dotdigitalgroup\Email\Model\Apiconnector\Client;
use \Dotdigitalgroup\Email\Model\Apiconnector\EngagementCloudAddressBookApi;

/**
 * Handle bulk data for importer.
 */
class Bulk
{
    /**
     * Legendary error message
     */
    const ERROR_UNKNOWN = 'Error unknown';

    /**
     * @var \Dotdigitalgroup\Email\Model\Config\Json
     */
    public $serializer;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Importer
     */
    protected $importerResource;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $helper;

    /**
     * @var \Dotdigitalgroup\Email\Helper\File
     */
    protected $fileHelper;

    /**
     * @var Client|EngagementCloudAddressBookApi
     */
    protected $client;

    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    private $contactFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    private $dateTime;

    /**
     * Bulk constructor.
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Helper\File $fileHelper
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importerResource
     * @param \Dotdigitalgroup\Email\Model\Config\Json $serializer
     * @param \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Helper\File $fileHelper,
        \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importerResource,
        \Dotdigitalgroup\Email\Model\Config\Json $serializer,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Magento\Framework\Stdlib\DateTime $dateTime
    ) {
        $this->helper = $helper;
        $this->fileHelper = $fileHelper;
        $this->serializer = $serializer;
        $this->importerResource = $importerResource;
        $this->contactFactory = $contactFactory;
        $this->dateTime = $dateTime;
    }

    /**
     * Sync.
     *
     * @param mixed $collection
     *
     * @return null
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

                if (! empty($file) && ! empty($addressBook) && $this->client) {
                    if ($this->fileHelper->isFilePathExistWithFallback($file)) {
                        //import contacts from csv file
                        $result = $this->client->postAddressBookContactsImport($file, $addressBook);
                        $this->_handleItemAfterSync($item, $result);
                    } else {
                        $item->setImportStatus(\Dotdigitalgroup\Email\Model\Importer::FAILED)
                            ->setMessage(__('CSV file does not exist in email or archive folder.'));
                        $this->importerResource->save($item);
                    }
                }
            }
        }
    }

    /**
     * Get addressbook by import type.
     *
     * @param string $importType
     * @param int $websiteId
     *
     * @return string
     */
    private function _getAddressBook($importType, $websiteId)
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
     * @param mixed $item
     * @param mixed $result
     *
     * @return null
     */
    public function _handleItemAfterSync($item, $result)
    {
        $curlError = $this->_checkCurlError($item);

        if (!$curlError) {
            if (isset($result->message) && !isset($result->id)) {
                $item->setImportStatus(\Dotdigitalgroup\Email\Model\Importer::FAILED)
                    ->setMessage($result->message);

                $this->importerResource->save($item);
            } elseif (isset($result->id) && !isset($result->message)) {
                $item->setImportStatus(\Dotdigitalgroup\Email\Model\Importer::IMPORTING)
                    ->setImportId($result->id)
                    ->setImportStarted($this->dateTime->formatDate(true))
                    ->setMessage('');
                $this->importerResource->save($item);
            } else {
                $message = (isset($result->message)) ? $result->message : self::ERROR_UNKNOWN;
                $item->setImportStatus(\Dotdigitalgroup\Email\Model\Importer::FAILED)
                    ->setMessage($message);

                //If result id
                if (isset($result->id)) {
                    $item->setImportId($result->id);
                }

                $this->importerResource->save($item);
            }
        }
    }

    /**
     * @param mixed $item
     *
     * @return bool
     */
    public function _checkCurlError($item)
    {
        //if curl error 28
        $curlError = $this->client->getCurlError();
        if ($curlError) {
            $item->setMessage($curlError)
                ->setImportStatus(\Dotdigitalgroup\Email\Model\Importer::FAILED);
            $this->importerResource->save($item);

            return true;
        }

        return false;
    }

    /**
     * @param $item
     * @param $apiContact
     * @param string|null $apiMessage
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    protected function handleSingleItemAfterSync($item, $apiContact, $apiMessage = null)
    {
        $curlError = $this->_checkCurlError($item);

        //no api connection error
        if (! $curlError) {
            //api response error
            if (isset($apiContact->message) or ! $apiContact) {
                $message = (isset($apiContact->message)) ? $apiContact->message : self::ERROR_UNKNOWN;
                $item->setImportStatus(\Dotdigitalgroup\Email\Model\Importer::FAILED)
                    ->setMessage($message);
            } else {
                $dateTime = $this->dateTime->formatDate(true);
                $item->setImportStatus(\Dotdigitalgroup\Email\Model\Importer::IMPORTED)
                    ->setImportFinished($dateTime)
                    ->setImportStarted($dateTime)
                    ->setMessage($apiMessage ?: '');
            }
            $this->importerResource->save($item);
        }
    }
}
