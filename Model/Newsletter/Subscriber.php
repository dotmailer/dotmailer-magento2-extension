<?php

namespace Dotdigitalgroup\Email\Model\Newsletter;

use Magento\Framework\Exception\LocalizedException;
use DotMailer\Api\Container;

class Subscriber
{

    const STATUS_SUBSCRIBED = 1;
    const STATUS_NOT_ACTIVE = 2;
    const STATUS_UNSUBSCRIBED = 3;
    const STATUS_UNCONFIRMED = 4;

    protected $_start;

    /**
     * Global number of subscriber updated.
     *
     * @var
     */
    protected $_countSubscriber = 0;

    protected $_file;
    protected $_config;
    protected $_helper;
    protected $_dateTime;
    protected $storeManager;
    protected $_scopeConfig;
    protected $_contactFactory;
    protected $_subscriberFactory;
    protected $_contactCollection;
    protected $_importerFactory;

    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Dotdigitalgroup\Email\Model\Resource\Contact\CollectionFactory $contactCollection,
        \Dotdigitalgroup\Email\Helper\File $file,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Helper\Config $config,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\DateTimeFactory $dateFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_importerFactory = $importerFactory;
        $this->_contactCollection = $contactCollection;
        $this->_file              = $file;
        $this->_helper            = $helper;
        $this->_config            = $config;
        $this->_subscriberFactory = $subscriberFactory;
        $this->_contactFactory    = $contactFactory;
        $this->_dateTime          = $dateFactory;
        $this->_scopeConfig       = $scopeConfig;
        $this->storeManager       = $storeManager;
    }

    /**
     * SUBSCRIBER SYNC.
     *
     * @return $this
     */
    public function sync()
    {
        $response     = array('success' => true, 'message' => '');
        $this->_start = microtime(true);
        $websites     = $this->_helper->getWebsites(true);
        $started      = false;

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

                if ($this->_countSubscriber && ! $started) {
                    $this->_helper->log('---------------------- Start subscriber sync -------------------');
                    $started = true;
                }
                // show message for any number of customers
                if ($numUpdated) {
                    $response['message'] .= '</br>' . $website->getName()
                        . ', updated subscribers = ' . $numUpdated;
                }
            }
        }

        //global number of subscribers to set the message
        if ($this->_countSubscriber) {
            //reponse message
            $message = 'Total time for sync : ' . gmdate("H:i:s",
                    microtime(true) - $this->_start);

            //put the message in front
            $message .= $response['message'];
            $result['message'] = $message;
        }

        return $response;
    }

    /**
     * Export subscribers per website.
     *
     * @param $website
     *
     * @return int
     * @throws LocalizedException
     */
    public function exportSubscribersPerWebsite($website)
    {
        $updated = 0;
        $limit   = $this->_helper->getSyncLimit($website->getId());
        //subscriber collection to import
        $subscribers = $this->_contactFactory->create()
            ->getSubscribersToImport($website, $limit);

        if ($subscribers->getSize()) {
            $subscribersFilename = strtolower($website->getCode()
                . '_subscribers_' . date('d_m_Y_Hi') . '.csv');
            //get mapped storename
            $subscriberStoreName = $this->_helper->getMappedStoreName($website);
            //file headers
            $this->_file->outputCSV($this->_file->getFilePath($subscribersFilename),
                array('Email', 'emailType', $subscriberStoreName));
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
                        array($email, 'Html', $storeName));
                    $updated++;
                } catch (\Exception $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
                }
            }
            $this->_helper->log('Subscriber filename: ' . $subscribersFilename);
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