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
    public $countSubscribers = 0;

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
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    public $orderCollection;

    /**
     * @var \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory
     */
    public $subscribersCollection;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    public $resource;

    /**
     * @var \Dotdigitalgroup\Email\Model\Apiconnector\SubscriberFactory
     */
    public $emailSubscriber;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory
     */
    public $emailContactResource;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    public $timezone;

    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Dotdigitalgroup\Email\Helper\File $file,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollection,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection,
        \Dotdigitalgroup\Email\Model\Apiconnector\SubscriberFactory $emailSubscriber,
        \Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory $contactResource,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->importerFactory   = $importerFactory;
        $this->file              = $file;
        $this->helper            = $helper;
        $this->subscriberFactory = $subscriberFactory;
        $this->contactFactory    = $contactFactory;
        $this->storeManager      = $storeManager;
        $this->resource = $resource;
        $this->subscribersCollection = $subscriberCollection;
        $this->orderCollection = $orderCollection;
        $this->emailSubscriber = $emailSubscriber;
        $this->emailContactResource = $contactResource;
        $this->timezone = $timezone;
    }

    /**
     * @return array
     */
    public function sync()
    {
        $response    = ['success' => true, 'message' => ''];
        $this->start = microtime(true);
        $websites    = $this->helper->getWebsites(true);

        foreach ($websites as $website) {
            //if subscriber is enabled and mapped
            $apiEnabled = $this->helper->isEnabled($website->getid());
            $subscriberEnabled
                = $this->helper->isSubscriberSyncEnabled($website->getid());
            $addressBook
                = $this->helper->getSubscriberAddressBook($website->getId());
            //enabled and mapped
            if ($apiEnabled && $addressBook && $subscriberEnabled) {
                //ready to start sync
                $numUpdated = $this->exportSubscribersPerWebsite($website);

                // show message for any number of customers
                if ($numUpdated) {
                    $response['message'] .= $website->getName() . ',  count = ' . $numUpdated;
                }
            }
        }
        //sync proccessed
        if ($this->countSubscribers) {
            $message = '----------- Subscribers sync ----------- : ' . gmdate('H:i:s', microtime(true) - $this->start) .
                ', updated = ' . $this->countSubscribers;
            $this->helper->log($message);
            $message .= $response['message'];
            $response['message'] = $message;
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
        $emailContactModel = $this->contactFactory->create();
        //Customer Subscribers
        $subscribersAreCustomers = $emailContactModel->getSubscribersToImport($website, $limit);
        //Guest Subscribers
        $subscribersAreGuest = $emailContactModel->getSubscribersToImport($website, $limit, false);
        $subscribersGuestEmails = $subscribersAreGuest->getColumnValues('email');
        $existInSales = [];
        if (! empty($subscribersGuestEmails)) {
            $existInSales = $this->checkInSales($subscribersGuestEmails);
        }
        $emailsNotInSales = array_diff($subscribersGuestEmails, $existInSales);
        $customerSubscribers = $subscribersAreCustomers->getColumnValues('email');
        $emailsWithNoSaleData = array_merge($emailsNotInSales, $customerSubscribers);
        //subscriber that are customer or/and the one that do not exist in sales order table.
        $subscribersWithNoSaleData = [];
        if (! empty($emailsWithNoSaleData)) {
            $subscribersWithNoSaleData = $emailContactModel
                ->getSubscribersToImportFromEmails($emailsWithNoSaleData);
        }
        if (! empty($subscribersWithNoSaleData)) {
            $updated += $this->exportSubscribers(
                $website,
                $subscribersWithNoSaleData
            );
            //add updated number for the website
            $this->countSubscribers += $updated;
        }
        //subscriber that are guest and also exist in sales order table.
        $subscribersWithSaleData = [];
        if (! empty($existInSales)) {
            $subscribersWithSaleData = $emailContactModel->getSubscribersToImportFromEmails($existInSales);
        }

        if (! empty($subscribersWithSaleData)) {
            $updated += $this->exportSubscribersWithSales($website, $subscribersWithSaleData);
            //add updated number for the website
            $this->countSubscribers += $updated;
        }
        return $updated;
    }

    /**
     * Get the store id from newsletter_subscriber, return default if not found.
     *
     * @param $email
     * @param $subscribers
     *
     * @return int
     */
    public function getStoreIdForSubscriber($email, $subscribers)
    {
        $defaultStore = 1;
        foreach ($subscribers as $subscriber) {
            if ($subscriber['subscriber_email'] == $email) {
                return $subscriber['store_id'];
            }
        }
        return $defaultStore;
    }

    /**
     * Export subscribers
     *
     * @param $website
     * @param $subscribers
     * @return int
     */
    public function exportSubscribers($website, $subscribers)
    {
        $updated = 0;
        $subscribersFilename = strtolower($website->getCode() . '_subscribers_' . date('d_m_Y_Hi') . '.csv');
        //get mapped storename
        $subscriberStorename = $this->helper->getMappedStoreName($website);
        //file headers
        $this->file->outputCSV(
            $this->file->getFilePath($subscribersFilename),
            ['Email', 'emailType', $subscriberStorename]
        );
        $subscriberFactory = $this->subscriberFactory->create();
        $subscribersData = $subscriberFactory->getCollection()
            ->addFieldToFilter(
                'subscriber_email',
                ['in' => $subscribers->getColumnValues('email')]
            )
            ->addFieldToSelect(['subscriber_email', 'store_id'])
            ->toArray();
        foreach ($subscribers as $subscriber) {
            $email = $subscriber->getEmail();
            $storeId = $this->getStoreIdForSubscriber(
                $email,
                $subscribersData['items']
            );
            $storeName = $this->storeManager->getStore($storeId)->getName();
            // save data for subscribers
            $this->file->outputCSV(
                $this->file->getFilePath($subscribersFilename),
                [$email, 'Html', $storeName]
            );
            //@codingStandardsIgnoreStart
            $subscriber->setSubscriberImported(1)->save();
            //@codingStandardsIgnoreEnd
            $updated++;
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
        return $updated;
    }

    /**
     * Check emails exist in sales order table
     *
     * @param $emails
     * @return array
     */
    public function checkInSales($emails)
    {
        $collection = $this->orderCollection->create()
            ->addFieldToFilter('customer_email', ['in' => $emails]);
        return $collection->getColumnValues('customer_email');
    }

    /**
     * @param $website
     * @param $subscribers
     * @return int
     */
    public function exportSubscribersWithSales($website, $subscribers)
    {
        $updated = 0;
        $subscriberIds = $headers = $emailContactIdEmail = [];

        foreach ($subscribers as $emailContact) {
            $emailContactIdEmail[$emailContact->getId()] = $emailContact->getEmail();
        }
        $subscribersFile = strtolower($website->getCode() . '_subscribers_with_sales_' . date('d_m_Y_Hi') . '.csv');
        $this->helper->log('Subscriber file with sales : ' . $subscribersFile);
        //get subscriber emails
        $emails = $subscribers->getColumnValues('email');

        //subscriber collection
        $collection = $this->getCollection($emails, $website->getId());
        //no subscribers found
        if ($collection->getSize() == 0) {
            return 0;
        }
        $mappedHash = $this->helper->getWebsiteSalesDataFields($website);
        $headers = $mappedHash;
        $headers[] = 'Email';
        $headers[] = 'EmailType';
        $this->file->outputCSV($this->file->getFilePath($subscribersFile), $headers);
        //subscriber data
        foreach ($collection as $subscriber) {
            $connectorSubscriber = $this->emailSubscriber->create();
            $connectorSubscriber->setMappingHash($mappedHash);
            $connectorSubscriber->setSubscriberData($subscriber);
            //count number of customers
            $index = array_search($subscriber->getSubscriberEmail(), $emailContactIdEmail);
            if ($index) {
                $subscriberIds[] = $index;
            }
            //contact email and email type
            $connectorSubscriber->setData($subscriber->getSubscriberEmail());
            $connectorSubscriber->setData('Html');
            // save csv file data
            $this->file->outputCSV($this->file->getFilePath($subscribersFile), $connectorSubscriber->toCSVArray());
            //clear collection and free memory
            $subscriber->clearInstance();
            $updated++;
        }

        $subscriberNum = count($subscriberIds);
        //@codingStandardsIgnoreStart
        if (is_file($this->file->getFilePath($subscribersFile))) {
            //@codingStandardsIgnoreEnd
            if ($subscriberNum > 0) {
                //register in queue with importer
                $check = $this->importerFactory->create()
                    ->registerQueue(
                        \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_SUBSCRIBERS,
                        '',
                        \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
                        $website->getId(),
                        $subscribersFile
                    );
                //set imported
                if ($check) {
                    $this->emailContactResource->create()
                        ->updateSubscribers($subscriberIds);
                }
            }
        }

        return $updated;
    }

    /**
     * @param $emails
     * @param int $websiteId
     * @return mixed
     */
    public function getCollection($emails, $websiteId = 0)
    {
        $salesOrder = $this->resource->getTableName('sales_order');
        $salesOrderItem = $this->resource->getTableName('sales_order_item');
        $catalogProductEntityInt = $this->resource->getTableName('catalog_product_entity_int');
        $eavAttribute = $this->resource->getTableName('eav_attribute');
        $eavAttributeOptionValue = $this->resource->getTableName('eav_attribute_option_value');
        $catalogCategoryProductIndex = $this->resource->getTableName('catalog_category_product');

        $collection = $this->subscribersCollection->create()
            ->addFieldToSelect([
                    'subscriber_email',
                    'store_id',
                    'subscriber_status'
                ]);

        //only when subscriber emails are set
        if (! empty($emails)) {
            $collection->addFieldToFilter('subscriber_email', $emails);
        }

        $alias = 'subselect';
        $statuses = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_STATUS,
            $websiteId
        );
        $statuses = explode(',', $statuses);

        $connection = $this->resource->getConnection();
        //@codingStandardsIgnoreStart
        $subselect = $connection->select()
            ->from(
                $salesOrder, [
                    'customer_email as s_customer_email',
                    'sum(grand_total) as total_spend',
                    'count(*) as number_of_orders',
                    'avg(grand_total) as average_order_value',
                ]
            )
            ->group('customer_email');
        //any order statuses selected
        if (! empty($statuses)) {
            $subselect->where('status in (?)', $statuses);
        }

        $columns = [
            'last_order_date' => new \Zend_Db_Expr(
                "(
                        SELECT created_at FROM $salesOrder 
                        WHERE customer_email = main_table.subscriber_email 
                        ORDER BY created_at DESC 
                        LIMIT 1
                )"
            ),
            'last_order_id' => new \Zend_Db_Expr(
                "(
                        SELECT entity_id FROM $salesOrder
                        WHERE customer_email = main_table.subscriber_email 
                        ORDER BY created_at DESC 
                        LIMIT 1
                )"
            ),
            'last_increment_id' => new \Zend_Db_Expr(
                "(
                        SELECT increment_id FROM $salesOrder
                        WHERE customer_email = main_table.subscriber_email 
                        ORDER BY created_at DESC 
                        LIMIT 1
                )"
            ),
            'first_category_id' => new \Zend_Db_Expr(
                "(
                        SELECT ccpi.category_id FROM $salesOrder as sfo
                        left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        left join $catalogCategoryProductIndex as ccpi on ccpi.product_id = sfoi.product_id
                        WHERE sfo.customer_email = main_table.subscriber_email
                        ORDER BY sfo.created_at ASC, sfoi.price DESC
                        LIMIT 1
                    )"
            ),
            'last_category_id' => new \Zend_Db_Expr(
                "(
                        SELECT ccpi.category_id FROM $salesOrder as sfo
                        left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        left join $catalogCategoryProductIndex as ccpi on ccpi.product_id = sfoi.product_id
                        WHERE sfo.customer_email = main_table.subscriber_email
                        ORDER BY sfo.created_at DESC, sfoi.price DESC
                        LIMIT 1
                    )"
            ),
            'product_id_for_first_brand' => new \Zend_Db_Expr(
                "(
                        SELECT sfoi.product_id FROM $salesOrder as sfo
                        left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        WHERE sfo.customer_email = main_table.subscriber_email and sfoi.product_type = 'simple'
                        ORDER BY sfo.created_at ASC, sfoi.price DESC
                        LIMIT 1
                    )"
            ),
            'product_id_for_last_brand' => new \Zend_Db_Expr(
                "(
                        SELECT sfoi.product_id FROM $salesOrder as sfo
                        left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        WHERE sfo.customer_email = main_table.subscriber_email and sfoi.product_type = 'simple'
                        ORDER BY sfo.created_at DESC, sfoi.price DESC
                        LIMIT 1
                    )"
            ),
            'week_day' => new \Zend_Db_Expr(
                "(
                        SELECT dayname(created_at) as week_day
                        FROM $salesOrder
                        WHERE customer_email = main_table.subscriber_email
                        GROUP BY week_day
                        HAVING COUNT(*) > 0
                        ORDER BY (COUNT(*)) DESC
                        LIMIT 1
                    )"
            ),
            'month_day' => new \Zend_Db_Expr(
                "(
                        SELECT monthname(created_at) as month_day
                        FROM $salesOrder
                        WHERE customer_email = main_table.subscriber_email
                        GROUP BY month_day
                        HAVING COUNT(*) > 0
                        ORDER BY (COUNT(*)) DESC
                        LIMIT 1
                    )"
            ),
            'most_category_id' => new \Zend_Db_Expr(
                "(
                        SELECT ccpi.category_id FROM $salesOrder as sfo
                        LEFT JOIN $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        LEFT JOIN $catalogCategoryProductIndex as ccpi on ccpi.product_id = sfoi.product_id
                        WHERE sfo.customer_email = main_table.subscriber_email AND ccpi.category_id is not null
                        GROUP BY category_id
                        HAVING COUNT(sfoi.product_id) > 0
                        ORDER BY COUNT(sfoi.product_id) DESC
                        LIMIT 1
                    )"
            )
        ];

        /**
         * CatalogStaging fix.
         * @todo this will fix https://github.com/magento/magento2/issues/6478
         */
        $rowIdExists = $this->isRowIdExistsInCatalogProductEntityId();

        if ($rowIdExists) {
            $mostData = new \Zend_Db_Expr(
                "(
                    SELECT eaov.value from $salesOrder sfo
                    LEFT JOIN $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                    LEFT JOIN $catalogProductEntityInt pei on pei.row_id = sfoi.product_id
                    LEFT JOIN $eavAttribute ea ON pei.attribute_id = ea.attribute_id
                    LEFT JOIN $eavAttributeOptionValue as eaov on pei.value = eaov.option_id
                    WHERE sfo.customer_email = main_table.subscriber_email AND ea.attribute_code = 'manufacturer' AND eaov.value is not null
                    GROUP BY eaov.value
                    HAVING count(*) > 0
                    ORDER BY count(*) DESC
                    LIMIT 1
                )"
            );
        } else {
            $mostData = new \Zend_Db_Expr(
                "(
                    SELECT eaov.value from $salesOrder sfo
                    LEFT JOIN $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                    LEFT JOIN $catalogProductEntityInt pei on pei.entity_id = sfoi.product_id
                    LEFT JOIN $eavAttribute ea ON pei.attribute_id = ea.attribute_id
                    LEFT JOIN $eavAttributeOptionValue as eaov on pei.value = eaov.option_id
                    WHERE sfo.customer_email = main_table.subscriber_email AND ea.attribute_code = 'manufacturer' AND eaov.value is not null
                    GROUP BY eaov.value
                    HAVING count(*) > 0
                    ORDER BY count(*) DESC
                    LIMIT 1
                )"
            );

        }

        $columns['most_brand'] = $mostData;
        $collection->getSelect()->columns(
            $columns
        );

        $collection->getSelect()
            ->joinLeft(
                [$alias => $subselect],
                "{$alias}.s_customer_email = main_table.subscriber_email"
            );
        //@codingStandardsIgnoreEnd
        return $collection;
    }

    /**
     * @return bool
     */
    protected function isRowIdExistsInCatalogProductEntityId()
    {
        $connection = $this->resource->getConnection();

        return $connection->tableColumnExists(
            $this->resource->getTableName('catalog_product_entity_int'),
            'row_id'
        );
    }

    /**
     * Un-subscribe suppressed contacts.
     * @return mixed
     */
    public function unsubscribe()
    {
        $limit = 5;
        $maxToSelect = 1000;
        $result['customers'] = 0;
        $date = $this->timezone->date()->sub(\DateInterval::createFromDateString('24 hours'));
        $suppressedEmails = [];

        // Datetime format string
        $dateString = $date->format(\DateTime::W3C);

        /**
         * Sync all suppressed for each store
         */
        $websites = $this->helper->getWebsites(true);
        foreach ($websites as $website) {
            $client = $this->helper->getWebsiteApiClient($website);
            $skip = $i = 0;
            $contacts = [];

            // Not enabled and valid credentials
            if (! $client) {
                continue;
            }

            //there is a maximum of request we need to loop to get more suppressed contacts
            for ($i=0; $i<= $limit;$i++) {
                $apiContacts = $client->getContactsSuppressedSinceDate($dateString, $maxToSelect , $skip);

                // skip no more contacts or the api request failed
                if(empty($apiContacts) || isset($apiContacts->message)) {
                    break;
                }
                $contacts = array_merge($contacts, $apiContacts);
                $skip += 1000;
            }

            // Contacts to un-subscribe
            foreach ($contacts as $apiContact) {
                if (isset($apiContact->suppressedContact)) {
                    $suppressedContact = $apiContact->suppressedContact;
                    $suppressedEmails[] = $suppressedContact->email;
                }
            }
        }
        //Mark suppressed contacts
        if (! empty($suppressedEmails)) {
            $this->emailContactResource->create()->unsubscribe($suppressedEmails);
        }
        return $result;
    }
}
