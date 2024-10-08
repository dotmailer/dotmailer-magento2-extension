<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type\Contact;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Helper\File;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\AbstractItemSyncer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\BulkItemPostProcessorFactory;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * @deprecated We now have a dedicated MergeManager class for merging batches.
 * @see \Dotdigitalgroup\Email\Model\Sync\Importer\Type\Contact\BulkJson
 */
class Bulk extends AbstractItemSyncer
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var File
     */
    private $fileHelper;

    /**
     * @var BulkItemPostProcessorFactory
     */
    protected $postProcessor;

    /**
     * Bulk constructor.
     * @param Data $helper
     * @param File $fileHelper
     * @param BulkItemPostProcessorFactory $postProcessor
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        Data $helper,
        File $fileHelper,
        BulkItemPostProcessorFactory $postProcessor,
        Logger $logger,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->fileHelper = $fileHelper;
        $this->postProcessor = $postProcessor;

        parent::__construct($logger, $data);
    }

    /**
     * Process.
     *
     * @param mixed $item
     * @return mixed
     */
    public function process($item)
    {
        $result = null;

        $file = $item->getImportFile();

        $addressBook = $this->_getAddressBook(
            $item->getImportType(),
            $item->getWebsiteId()
        );

        if (! empty($file) && ! empty($addressBook)) {
            if ($this->fileHelper->isFilePathExistWithFallback($file)) {
                //import contacts from csv file
                $result = $this->client->postAddressBookContactsImport($file, $addressBook);
            } else {
                $result = (object)['message' => __('CSV file does not exist in email or archive folder.')];
            }
        }

        return $result;
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
            case \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_CUSTOMER:
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
}
