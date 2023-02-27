<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;
use Magento\Newsletter\Model\Subscriber;
use Magento\Framework\DB\Sql\ExpressionFactory;
use Magento\Store\Model\Website;

class Contact extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory
     */
    private $subscribersCollection;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    private $customerCollection;

    /**
     * @var \Magento\Cron\Model\ScheduleFactory
     */
    private $scheduleFactory;

    /**
     * @var ExpressionFactory
     */
    private $expressionFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * Initialize resource.
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(Schema::EMAIL_CONTACT_TABLE, 'email_contact_id');
    }

    /**
     * Contact constructor.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollection
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
     * @param \Magento\Cron\Model\ScheduleFactory $schedule
     * @param ExpressionFactory $expressionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param Config $config
     * @param string|null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollection,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        \Magento\Cron\Model\ScheduleFactory $schedule,
        ExpressionFactory $expressionFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        Config $config,
        string $connectionName = null
    ) {
        $this->config                   = $config;
        $this->expressionFactory        = $expressionFactory;
        $this->scheduleFactory         = $schedule;
        $this->customerCollection       = $customerCollectionFactory;
        $this->subscribersCollection    = $subscriberCollection;
        $this->orderCollectionFactory   = $orderCollectionFactory;
        parent::__construct($context, $connectionName);
    }

    /**
     * Remove all contact_id from the table.
     *
     * @return int
     */
    public function deleteContactIds()
    {
        $conn = $this->getConnection();
        $num = $conn->update(
            $this->getTable(Schema::EMAIL_CONTACT_TABLE),
            ['contact_id' => $this->expressionFactory->create(["expression" => 'null'])],
            $conn->quoteInto(
                'contact_id is ?',
                $this->expressionFactory->create(["expression" => 'not null'])
            )
        );

        return $num;
    }

    /**
     * Reset the imported contacts.
     *
     * @param string|null $from
     * @param string|null $to
     * @return int
     */
    public function resetAllCustomers(string $from = null, string $to = null)
    {
        $conn = $this->getConnection();

        if ($from && $to) {
            $where = [
                'created_at >= ?' => $from . ' 00:00:00',
                'created_at <= ?' => $to . ' 23:59:59',
                'email_imported = ?' => 1
            ];
        } else {
            $where = ['email_imported = ?' => 1];
        }

        $num = $conn->update(
            $this->getTable(Schema::EMAIL_CONTACT_TABLE),
            ['email_imported' => 0],
            $where
        );

        return $num;
    }

    /**
     * Flag individual contacts for reimport
     *
     * @param array $customerIds
     * @return int
     */
    public function resetContacts(array $customerIds)
    {
        return $this->getConnection()
            ->update(
                $this->getTable(Schema::EMAIL_CONTACT_TABLE),
                ['email_imported' => 0],
                ['customer_id IN (?)' => $customerIds]
            );
    }

    /**
     * Set all imported subscribers for reimport.
     *
     * @param string|null $from
     * @param string|null $to
     * @return int
     */
    public function resetSubscribers(string $from = null, string $to = null)
    {
        $conn = $this->getConnection();

        if ($from && $to) {
            $where = [
                'created_at >= ?' => $from . ' 00:00:00',
                'created_at <= ?' => $to . ' 23:59:59',
                'subscriber_imported = ?' => 1
            ];
        } else {
            $where = ['subscriber_imported = ?' => 1];
        }

        $num = $conn->update(
            $this->getTable(Schema::EMAIL_CONTACT_TABLE),
            ['subscriber_imported' => 0],
            $where
        );

        return $num;
    }

    /**
     * Subscribe a batch of contacts in email_contact/newsletter table, supplying store ids to restrict the scope.
     *
     * @param array $storeContacts
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function subscribeByEmailAndStore(array $storeContacts)
    {
        if (!empty($storeContacts) && is_array($storeContacts)) {
            $write = $this->getConnection();
            $now = (new \DateTime('now', new \DateTimeZone('UTC')))
                ->format(\DateTime::ATOM);
            $updated = 0;

            foreach ($storeContacts as $storeId => $emails) {
                // resubscribe to email_contact
                $ecUpdated = $write->update(
                    $this->getMainTable(),
                    [
                        'is_subscriber' => '1',
                        'subscriber_status' => Subscriber::STATUS_SUBSCRIBED,
                        'suppressed' => '0',
                        'last_subscribed_at' => $now,
                        'updated_at' => $now
                    ],
                    [
                        "email IN (?)" => $emails,
                        "store_id = (?)" => $storeId
                    ]
                );

                // resubscribe to newsletter_subscriber
                $write->update(
                    $this->getTable('newsletter_subscriber'),
                    [
                        'subscriber_status' => Subscriber::STATUS_SUBSCRIBED,
                        'change_status_at' => $now,
                    ],
                    [
                        "subscriber_email IN (?)" => $emails,
                        "store_id = (?)" => $storeId
                    ]
                );

                $updated += $ecUpdated;
            }

            return $updated;
        }

        return 0;
    }

    /**
     * Unsubscribe contacts.
     *
     * @param array $emails
     * @param array $websiteIds
     * @param array $storeIds
     * @return int
     */
    public function unsubscribeByWebsiteAndStore(array $emails, array $websiteIds, array $storeIds = [])
    {
        if (! empty($emails) && is_array($emails)) {
            $write = $this->getConnection();
            $now = (new \DateTime('now', new \DateTimeZone('UTC')))
                ->format(\DateTime::ATOM);

            //un-subscribe from the email contact table.
            $updated = $write->update(
                $this->getMainTable(),
                [
                    'is_subscriber' => $this->expressionFactory->create(["expression" => 'null']),
                    'subscriber_status' => Subscriber::STATUS_UNSUBSCRIBED,
                    'suppressed' => '1',
                    'last_subscribed_at' => $this->expressionFactory->create(['expression' => 'null']),
                    'updated_at' => $now
                ],
                [
                    "email IN (?)" => $emails,
                    "website_id IN (?)" => $websiteIds
                ]
            );

            // un-subscribe newsletter subscribers
            $newsletterWhereConditions = (empty($storeIds)) ?
                ["subscriber_email IN (?)" => $emails] :
                [
                    "subscriber_email IN (?)" => $emails,
                    "store_id IN (?)" => $storeIds
                ];

            $write->update(
                $this->getTable('newsletter_subscriber'),
                [
                    'subscriber_status' => Subscriber::STATUS_UNSUBSCRIBED,
                    'change_status_at' => $now
                ],
                $newsletterWhereConditions
            );

            return $updated;
        }

        return 0;
    }

    /**
     * Create contacts.
     *
     * @param array $guests
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function insertGuests($guests)
    {
        $write = $this->getConnection();
        if (!empty($guests)) {
            $write->insertMultiple($this->getMainTable(), $guests);
        }
    }

    /**
     * Mark contact as a guest.
     *
     * @param array $guestEmails
     * @param string $websiteId
     */
    public function setContactsAsGuest($guestEmails, $websiteId)
    {
        $write = $this->getConnection();
        if ($guestEmails) {
            $where = [
                'email IN (?)' => $guestEmails,
                'website_id = ?' => $websiteId,
                'is_guest IS NULL'
            ];
            $data = ['is_guest' => 1];
            $write->update($this->getMainTable(), $data, $where);
        }
    }

    /**
     * Set suppressed for contact ids.
     *
     * @param array $suppressedContactIds
     *
     * @return int
     */
    public function setContactSuppressedForContactIds($suppressedContactIds)
    {
        if (empty($suppressedContactIds)) {
            return 0;
        }
        $conn = $this->getConnection();
        //update suppressed for contacts
        $updated = $conn->update(
            $this->getMainTable(),
            ['suppressed' => 1],
            ['email_contact_id IN(?)' => $suppressedContactIds]
        );

        return $updated;
    }

    /**
     * Update subscriber imported.
     *
     * @param array $emailContactIds
     * @return int
     */
    public function setSubscribersImportedByIds($emailContactIds)
    {
        if (empty($emailContactIds)) {
            return 0;
        }
        $write = $this->getConnection();

        $updated = $write->update(
            $this->getMainTable(),
            ['subscriber_imported' => 1],
            ["email_contact_id IN (?)" => $emailContactIds]
        );

        return $updated;
    }

    /**
     * Set imported by customer ids.
     *
     * @param array $ids
     * @param string|int $websiteId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setContactsImportedByCustomerIds($ids, $websiteId = 0)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            ['email_imported' => 1],
            [
                "customer_id IN (?)" => $ids,
                "website_id = ?" => $websiteId
            ]
        );
    }

    /**
     * Set imported by ids.
     *
     * @param array $ids
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setContactsImportedByIds($ids)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            ['email_imported' => 1],
            [
                "email_contact_id IN (?)" => $ids
            ]
        );
    }

    /**
     * Get last cron ran date.
     *
     * @param string $cronJob
     *
     * @return boolean|string
     */
    public function getDateLastCronRun($cronJob)
    {
        $collection = $this->scheduleFactory->create()
            ->getCollection()
            ->addFieldToFilter('status', \Magento\Cron\Model\Schedule::STATUS_SUCCESS)
            ->addFieldToFilter('job_code', $cronJob);
        //limit and order the results
        $collection->getSelect()
            ->limit(1)
            ->order('executed_at DESC');

        if ($collection->getSize() == 0) {
            return false;
        }
        $executedAt = $collection->getFirstItem()->getExecutedAt();

        return $executedAt;
    }
}
