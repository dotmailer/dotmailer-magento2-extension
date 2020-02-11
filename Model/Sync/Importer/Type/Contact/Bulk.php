<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type\Contact;

use Dotdigitalgroup\Email\Model\Sync\Importer\Type\AbstractItemSyncer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\BulkItemPostProcessorFactory;

/**
 * Handle bulk data for importer.
 */
class Bulk extends AbstractItemSyncer
{
    /**
     * @var BulkItemPostProcessorFactory
     */
    protected $postProcessor;

    /**
     * Bulk constructor.
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Helper\File $fileHelper
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importerResource
     * @param BulkItemPostProcessorFactory $postProcessor
     * @param array $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Helper\File $fileHelper,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importerResource,
        BulkItemPostProcessorFactory $postProcessor,
        array $data = []
    ) {
        $this->postProcessor = $postProcessor;

        parent::__construct($helper, $fileHelper, $serializer, $importerResource, $data);
    }

    /**
     * Process.
     *
     * @param mixed $item
     *
     * @return stdClass|null
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
