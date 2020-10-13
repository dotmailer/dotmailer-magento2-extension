<?php

namespace Dotdigitalgroup\Email\Model\Customer;

use Dotdigitalgroup\Email\Model\Sync\SyncInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Guest sync cronjob.
 */
class Guest implements SyncInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact
     */
    private $contactResource;

    /**
     * @var int
     */
    private $countGuests = 0;

    /**
     * @var mixed
     */
    private $start;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Dotdigitalgroup\Email\Helper\File
     */
    private $file;

    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    private $contactFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    private $importerFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Guest constructor.
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
     * @param \Dotdigitalgroup\Email\Helper\File $file
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Dotdigitalgroup\Email\Helper\File $file,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        StoreManagerInterface $storeManager
    ) {
        $this->importerFactory = $importerFactory;
        $this->contactFactory = $contactFactory;
        $this->contactResource = $contactResource;
        $this->helper = $helper;
        $this->file = $file;
        $this->storeManager = $storeManager;
    }

    /**
     * GUEST SYNC.
     *
     * @param \DateTime|null $from
     * @return null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sync(\DateTime $from = null)
    {
        $this->start = microtime(true);
        $websites    = $this->helper->getWebsites();

        foreach ($websites as $website) {
            //check if the guest is mapped and enabled
            $addressbook = $this->helper->getGuestAddressBook($website);
            $guestSyncEnabled = $this->helper->isGuestSyncEnabled($website);
            $apiEnabled = $this->helper->isEnabled($website);
            if ($addressbook && $guestSyncEnabled && $apiEnabled) {
                //sync guests for website
                $this->exportGuestPerWebsite($website);
            }
        }
        if ($this->countGuests) {
            $this->helper->log(
                '----------- Guest sync ----------- : ' .
                gmdate('H:i:s', microtime(true) - $this->start) .
                ', Total synced = ' . $this->countGuests
            );
        }
    }

    /**
     * Export guests for a website.
     *
     * @param \Magento\Store\Model\Website $website
     *
     * @return null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function exportGuestPerWebsite($website)
    {
        $onlySubscribers = $this->helper->isOnlySubscribersForContactSync($website->getWebsiteId());
        $contact = $this->contactFactory->create();
        $guests = ($onlySubscribers) ? $contact->getGuests($website, true) :
            $contact->getGuests($website);

        //found some guests
        if ($guests->getSize()) {
            $guestFilename = strtolower(
                $website->getCode() . '_guest_'
                . date('d_m_Y_His') . '.csv'
            );
            $this->helper->log('Guest file: ' . $guestFilename);

            $this->file->outputCSV(
                $this->file->getFilePath($guestFilename),
                $this->getGuestColumns($website)
            );
            foreach ($guests as $guest) {
                $this->outputCsvToFile($guest, $guestFilename);
            }
            if ($this->countGuests) {
                //register in queue with importer
                $this->importerFactory->create()
                    ->registerQueue(
                        \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_GUEST,
                        '',
                        \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
                        $website->getId(),
                        $guestFilename
                    );
            }
        }
    }

    /**
     * @param $guest
     * @param $guestFilename
     * @param $website
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function outputCsvToFile($guest, $guestFilename)
    {
        $email = $guest->getEmail();
        $guest->setEmailImported(\Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_IMPORTED);

        $this->contactResource->save($guest);

        $storeId = $guest->getData('store_id');
        $store = $this->storeManager->getStore($storeId);

        $storeViewName = $store->getName();
        $websiteName = $store->getWebsite()->getName();
        $storeName = $store->getGroup()->getName();

        // save data for guests
        $this->file->outputCSV(
            $this->file->getFilePath($guestFilename),
            [$email, 'Html', $websiteName, $storeName, $storeViewName]
        );
        ++$this->countGuests;
    }

    /**
     * @param \Magento\Store\Model\Website $website
     * @return array
     */
    private function getGuestColumns(\Magento\Store\Model\Website $website)
    {
        $storeName = $website->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_MAPPING_CUSTOMER_STORENAME
        );

        $websiteName = $website->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_WEBSITE_NAME
        );

        $storeNameAdditional = $website->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_STORE_NAME_ADDITIONAL
        );

        $storeName = ($storeName) ? $storeName : '';
        $websiteName = ($websiteName) ? $websiteName : '';
        $storeNameAdditional = ($storeNameAdditional) ? $storeNameAdditional : '';

        return ['Email', 'emailType' , $websiteName, $storeNameAdditional, $storeName];
    }
}
