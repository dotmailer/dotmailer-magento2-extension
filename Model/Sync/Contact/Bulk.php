<?php

namespace Dotdigitalgroup\Email\Model\Sync\Contact;

/**
 * Handle bulk data for importer.
 */
class Bulk
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Importer
     */
    protected $importerResource;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $helper;

    /**
     * @var \Dotdigitalgroup\Email\Model\Apiconnector\Client
     */
    protected $client;

    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    private $contactFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\Config\Json
     */
    public $serializer;

    /**
     * Bulk constructor.
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importerResource
     * @param \Dotdigitalgroup\Email\Model\Config\Json $serializer
     * @param \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importerResource,
        \Dotdigitalgroup\Email\Model\Config\Json $serializer,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
    ) {
        $this->helper         = $helper;
        $this->serializer     = $serializer;
        $this->importerResource = $importerResource;
        $this->contactFactory = $contactFactory;
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
                    if ($this->helper->fileHelper->isFilePathExistWithFallback($file)) {
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
     * @param mixed  $item
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
                    ->setImportStarted(time())
                    ->setMessage('');
                $this->importerResource->save($item);
            } else {
                $message = (isset($result->message)) ? $result->message : 'Error unknown';
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
}
