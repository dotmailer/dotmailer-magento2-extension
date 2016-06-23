<?php

namespace Dotdigitalgroup\Email\Model\Customer;

class Guest
{
    /**
     * @var int
     */
    protected $_countGuests = 0;
    /**
     * @var
     */
    protected $_start;
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Dotdigitalgroup\Email\Helper\File
     */
    protected $_file;
    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    protected $_contactFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    protected $_importerFactory;

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
        $this->_importerFactory = $importerFactory;
        $this->_contactFactory = $contactFactory;
        $this->_helper = $helper;
        $this->_file = $file;
    }

    /**
     * GUEST SYNC.
     */
    public function sync()
    {
        $this->_start = microtime(true);
        $websites = $this->_helper->getWebsites();
        $started = false;

        foreach ($websites as $website) {

            //check if the guest is mapped and enabled
            $addresbook = $this->_helper->getGuestAddressBook($website);
            $guestSyncEnabled = $this->_helper->isGuestSyncEnabled($website);
            $apiEnabled = $this->_helper->isEnabled($website);
            if ($addresbook && $guestSyncEnabled && $apiEnabled) {

                //sync guests for website
                $this->exportGuestPerWebsite($website);

                if ($this->_countGuests && !$started) {
                    $this->_helper->log('----------- Start guest sync ----------');
                    $started = true;
                }
            }
        }
        if ($this->_countGuests) {
            $this->_helper->log('---- End Guest total time for guest sync : '
                . gmdate('H:i:s', microtime(true) - $this->_start));
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
        $guests = $this->_contactFactory->create()
            ->getGuests($website);
        //found some guests
        if ($guests->getSize()) {
            $guestFilename = strtolower($website->getCode() . '_guest_'
                . date('d_m_Y_Hi') . '.csv');
            $this->_helper->log('Guest file: ' . $guestFilename);
            $storeName = $this->_helper->getMappedStoreName($website);
            $this->_file->outputCSV($this->_file->getFilePath($guestFilename),
                ['Email', 'emailType', $storeName]);

            foreach ($guests as $guest) {
                $email = $guest->getEmail();
                try {
                    //@codingStandardsIgnoreStart
                    $guest->setEmailImported(\Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_IMPORTED)
                        ->save();
                    //@codingStandardsIgnoreEnd
                    $storeName = $website->getName();
                    // save data for guests
                    $this->_file->outputCSV($this->_file->getFilePath($guestFilename),
                        [$email, 'Html', $storeName]);
                    ++$this->_countGuests;
                } catch (\Exception $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
                }
            }
            if ($this->_countGuests) {
                //register in queue with importer
                $this->_importerFactory->create()
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
