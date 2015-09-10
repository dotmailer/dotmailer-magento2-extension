<?php

namespace  Dotdigitalgroup\Email\Model\Newsletter;

class Subscriber
{
    const STATUS_SUBSCRIBED     = 1;
    const STATUS_NOT_ACTIVE     = 2;
    const STATUS_UNSUBSCRIBED   = 3;
    const STATUS_UNCONFIRMED    = 4;

    protected $_start;

    /**
     * Global number of subscriber updated.
     * @var
     */
    protected $_countSubscriber = 0;

	protected $_file;
	protected $_config;
	protected $_helper;
	protected $_dateTime;
	protected $storeManager;
	protected $_scopeConfig;

	public function __construct(
		\Dotdigitalgroup\Email\Helper\File $file,
		\Dotdigitalgroup\Email\Helper\Data $helper,
		\Dotdigitalgroup\Email\Helper\Config $config,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Magento\Framework\Stdlib\DateTime\DateTimeFactory $dateFactory,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
	)
	{
		$this->_file = $file;
		$this->_helper = $helper;
		$this->_config = $config;
		$this->_dateTime = $dateFactory;
		$this->_scopeConfig = $scopeConfig;
		$this->storeManager = $storeManager;
		$this->_objectManager = $objectManager;
	}

    /**
     * SUBSCRIBER SYNC.
     * @return $this
     */
    public function sync()
    {
        $response = array('success' => true, 'message' => '');
        $this->_start = microtime(true);
		$websites = $this->_helper->getWebsites(true);

        foreach ($websites as $website) {
            //if subscriber is enabled and mapped
	        $apiEnabled         = $this->_helper->isEnabled($website->getid());
	        $subscriberEnaled   = $this->_helper->getSubscriberSyncEnabled($website->getid());
	        $addressBook        = $this->_helper->getSubscriberAddressBook($website->getId());
	        //enabled and mapped
            if ($apiEnabled && $addressBook && $subscriberEnaled) {
	            //ready to start sync
	            if (!$this->_countSubscriber)
	                $this->_helper->log('---------------------- Start subscriber sync -------------------');

                $numUpdated = $this->exportSubscribersPerWebsite($website);
                // show message for any number of customers
                if ($numUpdated)
                    $response['message'] .=  '</br>' . $website->getName() . ', updated subscribers = ' . $numUpdated;
            }
        }

        //global number of subscribers to set the message
        if ($this->_countSubscriber) {
            //reponse message
            $message = 'Total time for sync : ' . gmdate("H:i:s", microtime(true) - $this->_start);

            //put the message in front
            $message .= $response['message'];
            $result['message'] = $message;
        }

        return $response;
    }

    /**
     * Export subscriber per website.
     *
     * @return int
     */
    public function exportSubscribersPerWebsite($website)
    {
        $updated = 0;
        $limit = $this->_helper->getSyncLimit($website->getId());
	    //subscribr collectio to import
	    $subscribers = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Contact')
		    ->getSubscribersToImport($website, $limit);


	    if (count($subscribers)) {
            $subscribersFilename = strtolower($website->getCode() . '_subscribers_' . date('d_m_Y_Hi') . '.csv');
            //get mapped storename
		    $subscriberStoreName = $this->_helper->getMappedStoreName($website);
            //file headers
            $this->_file->outputCSV($this->_file->getFilePath($subscribersFilename), array('Email', 'emailType', $subscriberStoreName));
            //write subscriber data to csv file
		    foreach ($subscribers as $subscriber) {
                try{
                    $email = $subscriber->getEmail();
                    $subscriber->setSubscriberImported(1)->save();
	                $subscriberModel = $this->_objectManager->create('Magento\Newsletter\Model\Subscriber')
		                ->loadByEmail($email);

	                $storeName = $this->storeManager->getStore($subscriber->getStoreId())->getName();
                    // save data for subscribers
                    $this->_file->outputCSV($this->_file->getFilePath($subscribersFilename), array($email, 'Html', $storeName));
                    $updated++;
                }catch (\Exception $e){
                }
            }
            $this->_helper->log('Subscriber filename: ' . $subscribersFilename);
            //register in queue with importer
			$this->_objectManager->create('Dotdigitalgroup\Email\Model\Proccessor')->registerQueue(
                \Dotdigitalgroup\Email\Model\Proccessor::IMPORT_TYPE_SUBSCRIBERS,
                '',
				\Dotdigitalgroup\Email\Model\Proccessor::MODE_BULK,
                $website->getId(),
                $subscribersFilename
            );
        }
        //add updated number for the website
        $this->_countSubscriber += $updated;
        return $updated;
    }

    /**
     * Unsubscribe suppressed contacts.
     * @param bool $force set 10years old
     * @return mixed
     */
    public function unsubscribe($force = false)
    {
	    $limit = 5;
	    $max_to_select = 1000;
	    $result['customers'] = 0;
	    $date = new \Zend_Date();
	    $date->subHour(24);

        // force sync all customers
        if($force)
            $date = $date->subYear(10);
        // datetime format string
        $dateString = $date->toString(\Zend_Date::W3C);
        /**
         * 1. Sync all suppressed for each store
         */
	    $websites = $this->_helper->getWebsites(true);
        foreach ($websites as $website) {

	        $apiEnabled = $this->_helper->isEnabled($website);
	        //no enabled and valid credentials
            if (! $apiEnabled)
                continue;

	        $skip = $i = 0;
	        $contacts = array();
	        $client = $this->_helper->getWebsiteApiClient($website);

	        //there is a maximum of request we need to loop to get more suppressed contacts
            for ($i=0; $i<= $limit;$i++) {
                $apiContacts = $client->getContactsSuppressedSinceDate($dateString, $max_to_select , $skip);

                // skip no more contacts or the api request failed
                if(empty($apiContacts) || isset($apiContacts->message))
                    break;

                $contacts = array_merge($contacts, $apiContacts);
                $skip += 1000;
            }
            $subscriberBookId = $this->_helper->getSubscriberAddressBook($website);
            // suppressed contacts to unsubscibe
            foreach ($contacts as $apiContact) {
                if (isset($apiContact->suppressedContact)) {
                    $suppressedContact = $apiContact->suppressedContact;
                    $email      = $suppressedContact->email;
                    $contactId  = $suppressedContact->id;
                    try{
                        /**
                         * 2. Remove subscriber from the address book.
                         */

	                    $subscriber = $this->_objectManager->create('Magento\Newsletter\Model\Subscriber')
		                    ->loadByEmail($email);

	                    if ($subscriber->getStatus() == \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED) {
                            $subscriber->setStatus(\Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED);
                            $subscriber->save();
                            // remove from subscriber address-book
                            $client->deleteAddressBookContact($subscriberBookId, $contactId);
                        }
                        //mark contact as suppressed and unsubscribe

                        $contactCollection = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Contact')->getCollection()
                            ->addFieldToFilter('email', $email)
                            ->addFieldToFilter('website_id', $website->getId());

                        //unsubscribe from the email contact table.
                        foreach ($contactCollection as $contact) {
                            $contact->setIsSubscriber(null)
                                ->setSuppressed('1')->save();
                        }
                    }catch (\Exception $e){

                    }
                }
            }
        }
        return $result;
    }
}