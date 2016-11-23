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
    public $start;

    /**
     * Global number of subscriber updated.
     *
     * @var int
     */
    public $countSubscriber = 0;

    /**
     * @var \Dotdigitalgroup\Email\Helper\File
     */
    public $file;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    public $contactFactory;
    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    public $subscriberFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    public $importerFactory;

    /**
     * Subscriber constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
     * @param \Dotdigitalgroup\Email\Helper\File $file
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Dotdigitalgroup\Email\Helper\File $file,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->importerFactory   = $importerFactory;
        $this->file              = $file;
        $this->helper            = $helper;
        $this->subscriberFactory = $subscriberFactory;
        $this->contactFactory    = $contactFactory;
        $this->storeManager      = $storeManager;
    }

    /**
     * @return array
     */
    public function sync()
    {
        $response    = ['success' => true, 'message' => ''];
        $this->start = microtime(true);
        $websites    = $this->helper->getWebsites(true);
        $started     = false;

        foreach ($websites as $website) {
            //if subscriber is enabled and mapped
            $apiEnabled = $this->helper->isEnabled($website->getid());
            $subscriberEnaled
                = $this->helper->isSubscriberSyncEnabled($website->getid());
            $addressBook
                        = $this->helper->getSubscriberAddressBook($website->getId());
            //enabled and mapped
            if ($apiEnabled && $addressBook && $subscriberEnaled) {
                //ready to start sync
                $numUpdated = $this->exportSubscribersPerWebsite($website);

                if ($this->countSubscriber && !$started) {
                    $this->helper->log('---------------------- Start subscriber sync -------------------');
                    $started = true;
                }
                // show message for any number of customers
                if ($numUpdated) {
                    $response['message'] .= '</br>' . $website->getName()
                        . ', updated subscribers = ' . $numUpdated;
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
        $limit = $this->helper->getSyncLimit($website->getId());
        //subscriber collection to import
        $subscribers = $this->contactFactory->create()
            ->getSubscribersToImport($website, $limit);

        if ($subscribers->getSize()) {
            $subscribersFilename = strtolower($website->getCode()
                . '_subscribers_' . date('d_m_Y_Hi') . '.csv');
            //get mapped storename
            $subscriberStoreName = $this->helper->getMappedStoreName($website);
            //file headers
            $this->file->outputCSV(
                $this->file->getFilePath($subscribersFilename),
                ['Email', 'emailType', $subscriberStoreName]
            );

            $emails = $subscribers->getColumnValues('email');

            $subscriberFactory = $this->subscriberFactory->create();
            $subscribersData   = $subscriberFactory->getCollection()
                ->addFieldToFilter('subscriber_email', ['in' => $emails])
                ->addFieldToSelect(['subscriber_email', 'store_id'])
                ->toArray();

            foreach ($subscribers as $subscriber) {
                $email     = $subscriber->getEmail();
                $storeId   = $this->getStoreIdForSubscriber($email, $subscribersData['items']);

                $storeName = $this->storeManager->getStore($storeId)
                    ->getName();

                // save data for subscribers
                $this->file->outputCSV(
                    $this->file->getFilePath($subscribersFilename),
                    [$email, 'Html', $storeName]
                );
                //@codingStandardsIgnoreStart
                $subscriber->setSubscriberImported(1)->save();
                //@codingStandardsIgnoreEnd
                ++$updated;
            }

            $this->helper->log('Subscriber filename: ' . $subscribersFilename);
            //register in queue with importer
            $this->importerFactory->create()
                ->registerQueue(
                    \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_SUBSCRIBERS,
                    '',
                    \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
                    $website->getId(),
                    $subscribersFilename
                );
        }
        //add updated number for the website
        $this->countSubscriber += $updated;

        return $updated;
    }

    /**
     * @param $email
     * @param $subscribers
     *
     * @return bool
     */
    public function getStoreIdForSubscriber($email, $subscribers)
    {

        foreach ($subscribers as $subscriber) {
            if ($subscriber['subscriber_email'] == $email) {
                return $subscriber['store_id'];
            }
        }

        return false;
    }
}
