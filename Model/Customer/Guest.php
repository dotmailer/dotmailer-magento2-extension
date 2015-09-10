<?php

namespace Dotdigitalgroup\Email\Model\Customer;

class Guest
{
    protected $_countGuests = 0;
    protected $_start;
	protected $_helper;
	protected $_file;
	protected $_objectManager;

	public function __construct(
		\Dotdigitalgroup\Email\Helper\File $file,
		\Dotdigitalgroup\Email\Helper\Data $helper,
		\Magento\Framework\ObjectManagerInterface $objectManager
	)
	{
		$this->_helper = $helper;
		$this->_file = $file;
		$this->_objectManager = $objectManager;
	}
	/**
	 * GUEST SYNC.
	 */
	public function sync()
    {
        $this->_start = microtime(true);
	    $websites = $this->_helper->getWebsites();

	    foreach($websites as $website) {

	        //check if the guest is mapped and enabled
	        $addresbook = $this->_helper->getGuestAddressBook($website);
		    $guestSyncEnabled = $this->_helper->getGuestSyncEnabled($website);
		    $apiEnabled = $this->_helper->isEnabled($website);
	        if ($addresbook && $guestSyncEnabled && $apiEnabled) {

		        //ready to start sync
		        if (!$this->_countGuests)
		            $this->_helper->log('----------- Start guest sync ----------');

		        //sync guests for website
		        $this->exportGuestPerWebsite($website);
	        }
        }
	    if ($this->_countGuests)
            $this->_helper->log('---- End Guest total time for guest sync : ' . gmdate("H:i:s", microtime(true) - $this->_start));
    }

    public function exportGuestPerWebsite( $website)
    {
	    $guests = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Contact')->getGuests($website);
        //found some guests
	    if ($guests->getSize()) {
            $guestFilename = strtolower($website->getCode() . '_guest_' . date('d_m_Y_Hi') . '.csv');
            $this->_helper->log('Guest file: ' . $guestFilename);
            $storeName = $this->_helper->getMappedStoreName($website);
            $this->_file->outputCSV($this->_file->getFilePath($guestFilename), array('Email', 'emailType', $storeName));

            foreach ($guests as $guest) {
                $email = $guest->getEmail();
                try{
                    $guest->setEmailImported(\Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_IMPORTED)
                        ->save();
                    $storeName = $website->getName();
                    // save data for guests
                    $this->_file->outputCSV($this->_file->getFilePath($guestFilename), array($email, 'Html', $storeName));
                    $this->_countGuests++;
                }catch (\Exception $e){

                }
            }
            if ($this->_countGuests) {
                //register in queue with importer
	            $this->_objectManager->create('Dotdigitalgroup\Email\Model\Proccessor')
	                ->registerQueue(
                    \Dotdigitalgroup\Email\Model\Proccessor::IMPORT_TYPE_GUEST,
                    '',
                    \Dotdigitalgroup\Email\Model\Proccessor::MODE_BULK,
                    $website->getId(),
                    $guestFilename
                );
            }
        }
    }
}