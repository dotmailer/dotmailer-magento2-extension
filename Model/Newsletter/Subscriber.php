<?php

namespace Dotdigitalgroup\Email\Model\Newsletter;

use Magento\Framework\Exception\LocalizedException;

class Subscriber
{
    const STATUS_SUBSCRIBED = 1;
    const STATUS_NOT_ACTIVE = 2;
    const STATUS_UNSUBSCRIBED = 3;
    const STATUS_UNCONFIRMED = 4;

    /**
     * @var
     */
    protected $_start;

    /**
     * Global number of subscriber updated.
     *
     * @var int
     */
    protected $_countSubscriber = 0;

    /**
     * @var \Dotdigitalgroup\Email\Helper\File
     */
    protected $_file;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    
    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    protected $_contactFactory;
    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $_subscriberFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    protected $_importerFactory;

    /**
     * Subscriber constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Magento\Newsletter\Model\SubscriberFactory  $subscriberFactory
     * @param \Dotdigitalgroup\Email\Model\ContactFactory  $contactFactory
     * @param \Dotdigitalgroup\Email\Helper\File           $file
     * @param \Dotdigitalgroup\Email\Helper\Data           $helper
     * @param \Magento\Store\Model\StoreManagerInterface   $storeManager
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Dotdigitalgroup\Email\Helper\File $file,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_importerFactory = $importerFactory;
        $this->_file = $file;
        $this->_helper = $helper;
        $this->_subscriberFactory = $subscriberFactory;
        $this->_contactFactory = $contactFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * SUBSCRIBER SYNC.
     *
     * @return $this
     */
    public function sync()
    {
        $response = ['success' => true, 'message' => ''];
        $this->_start = microtime(true);
        $websites = $this->_helper->getWebsites(true);
        $started = false;

        foreach ($websites as $website) {
            //if subscriber is enabled and mapped
            $apiEnabled = $this->_helper->isEnabled($website->getid());
            $subscriberEnaled
                        = $this->_helper->isSubscriberSyncEnabled($website->getid());
            $addressBook
                        = $this->_helper->getSubscriberAddressBook($website->getId());
            //enabled and mapped
            if ($apiEnabled && $addressBook && $subscriberEnaled) {
                //ready to start sync
                $numUpdated = $this->exportSubscribersPerWebsite($website);

                if ($this->_countSubscriber && !$started) {
                    $this->_helper->log('---------------------- Start subscriber sync -------------------');
                    $started = true;
                }
                // show message for any number of customers
                if ($numUpdated) {
                    $response['message'] .= '</br>'.$website->getName()
                        .', updated subscribers = '.$numUpdated;
                }
            }
        }

        return $response;
    }

    /**
     * Export subscribers per website.
     *
     * @param $website
     *
     * @return int
     *
     * @throws LocalizedException
     */
    public function exportSubscribersPerWebsite($website)
    {
        $updated = 0;
        $limit = $this->_helper->getSyncLimit($website->getId());
        //subscriber collection to import
        $subscribers = $this->_contactFactory->create()
            ->getSubscribersToImport($website, $limit);

        if ($subscribers->getSize()) {
            $subscribersFilename = strtolower($website->getCode()
                .'_subscribers_'.date('d_m_Y_Hi').'.csv');
            //get mapped storename
            $subscriberStoreName = $this->_helper->getMappedStoreName($website);
            //file headers
            $this->_file->outputCSV($this->_file->getFilePath($subscribersFilename),
                ['Email', 'emailType', $subscriberStoreName]);
            //write subscriber data to csv file
            foreach ($subscribers as $subscriber) {
                try {
                    $email = $subscriber->getEmail();
                    $subscriber->setSubscriberImported(1)
                        ->save();
                    $subscriberFactory = $this->_subscriberFactory->create()
                        ->loadByEmail($email);

                    $storeName
                        = $this->storeManager->getStore($subscriberFactory->getStoreId())
                        ->getName();
                    // save data for subscribers
                    $this->_file->outputCSV($this->_file->getFilePath($subscribersFilename),
                        [$email, 'Html', $storeName]);
                    ++$updated;
                } catch (\Exception $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
                }
            }
            $this->_helper->log('Subscriber filename: '.$subscribersFilename);
            //register in queue with importer
            $this->_importerFactory->create()
                ->registerQueue(
                    \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_SUBSCRIBERS,
                    '',
                    \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
                    $website->getId(),
                    $subscribersFilename
                );
        }
        //add updated number for the website
        $this->_countSubscriber += $updated;

        return $updated;
    }
}
