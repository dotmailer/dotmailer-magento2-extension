<?php

namespace Dotdigitalgroup\Email\Model\Customer;

class Guest
{
    /**
     * @var int
     */
    public $countGuests = 0;
    /**
     * @var
     */
    public $start;
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var \Dotdigitalgroup\Email\Helper\File
     */
    public $file;
    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    public $contactFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    public $importerFactory;

    /**
     * Guest constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
     * @param \Dotdigitalgroup\Email\Helper\File $file
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Dotdigitalgroup\Email\Helper\File $file,
        \Dotdigitalgroup\Email\Helper\Data $helper
    ) {
        $this->importerFactory = $importerFactory;
        $this->contactFactory = $contactFactory;
        $this->helper = $helper;
        $this->file = $file;
    }

    /**
     * GUEST SYNC.
     */
    public function sync()
    {
        $this->start = microtime(true);
        $websites    = $this->helper->getWebsites();

        foreach ($websites as $website) {
            //check if the guest is mapped and enabled
            $addresbook = $this->helper->getGuestAddressBook($website);
            $guestSyncEnabled = $this->helper->isGuestSyncEnabled($website);
            $apiEnabled = $this->helper->isEnabled($website);
            if ($addresbook && $guestSyncEnabled && $apiEnabled) {
                //sync guests for website
                $this->exportGuestPerWebsite($website);
            }
        }
        if ($this->countGuests) {
            $this->helper->log('----------- Guest sync ----------- : ' .
                gmdate('H:i:s', microtime(true) - $this->start) . ', Total synced = ' . $this->countGuests);
        }
    }

    /**
     * Export guests for a website.
     *
     * @param $website
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function exportGuestPerWebsite($website)
    {
        $guests = $this->contactFactory->create()
            ->getGuests($website);
        //found some guests
        if ($guests->getSize()) {
            $guestFilename = strtolower($website->getCode() . '_guest_'
                . date('d_m_Y_Hi') . '.csv');
            $this->helper->log('Guest file: ' . $guestFilename);
            $storeName = $this->helper->getMappedStoreName($website);
            $this->file->outputCSV(
                $this->file->getFilePath($guestFilename),
                ['Email', 'emailType', $storeName]
            );

            foreach ($guests as $guest) {
                $email = $guest->getEmail();
                try {
                    //@codingStandardsIgnoreStart
                    $guest->setEmailImported(\Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_IMPORTED);
                    $guest->getResource()->save($guest);
                    //@codingStandardsIgnoreEnd
                    $storeName = $website->getName();
                    // save data for guests
                    $this->file->outputCSV(
                        $this->file->getFilePath($guestFilename),
                        [$email, 'Html', $storeName]
                    );
                    ++$this->countGuests;
                } catch (\Exception $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
                }
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
}
