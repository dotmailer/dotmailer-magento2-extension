<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Contact;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'email_contact_id';

    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * Initialize resource collection.
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(
            \Dotdigitalgroup\Email\Model\Contact::class,
            \Dotdigitalgroup\Email\Model\ResourceModel\Contact::class
        );
    }

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        $this->subscriberFactory = $subscriberFactory;
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
        );
    }

    /**
     * Load contact by customer id.
     *
     * @param int $customerId
     *
     * @return bool|\Dotdigitalgroup\Email\Model\Contact
     */
    public function loadByCustomerId($customerId)
    {
        $collection = $this->addFieldToFilter('customer_id', $customerId)
            ->setPageSize(1);

        if ($collection->getSize()) {
            return $collection->getFirstItem();
        }

        return false;
    }

    /**
     * Load contact by customer id and website id.
     *
     * @param string|int $customerId
     * @param string|int $websiteId
     * @return bool|\Dotdigitalgroup\Email\Model\Contact
     */
    public function loadByCustomerIdAndWebsiteId($customerId, $websiteId)
    {
        $collection = $this->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('website_id', $websiteId)
            ->setPageSize(1);

        if ($collection->getSize()) {
            return $collection->getFirstItem();
        }

        return false;
    }

    /**
     * Load Contact by Email.
     *
     * @param string $email
     * @param int $websiteId
     *
     * @return bool|\Dotdigitalgroup\Email\Model\Contact
     */
    public function loadByCustomerEmail($email, $websiteId)
    {
        $collection = $this->addFieldToFilter('email', $email)
            ->addFieldToFilter('website_id', $websiteId)
            ->setPageSize(1);

        if ($collection->getSize()) {
            return $collection->getFirstItem();
        }

        return false;
    }

    /**
     * Load all customers matching the supplied customer id.
     *
     * @param string $id
     * @return $this
     */
    public function loadCustomersById($id)
    {
        return $this->addFieldToFilter('customer_id', $id);
    }

    /**
     * Get subscribers who are customers.
     *
     * @param array $storeIds
     * @param int $limit
     * @param int $offset
     *
     * @return $this
     */
    public function getSubscribersToImportByStoreIds(array $storeIds, $limit, $offset)
    {
        $collection = $this->addFieldToFilter('is_subscriber', '1')
            ->addFieldToFilter('subscriber_status', '1')
            ->addFieldToFilter('subscriber_imported', 0)
            ->addFieldToFilter('store_id', ['in' => $storeIds]);

        $collection->getSelect()->limit($limit, $offset);

        return $collection;
    }

    /**
     * Get all not imported guests for a website.
     *
     * @param int|string $websiteId
     * @param boolean $onlySubscriber
     * @param int $pageSize
     * @param int $offset
     * @return Collection
     */
    public function getGuests($websiteId, $onlySubscriber, $pageSize, $offset)
    {
        $guestCollection = $this->addFieldToFilter('is_guest', ['notnull' => true])
            ->addFieldToFilter('email_imported', 0)
            ->addFieldToFilter('website_id', $websiteId)
            ->addFieldToFilter('customer_id', 0);

        if ($onlySubscriber) {
            $guestCollection->addFieldToFilter('is_subscriber', 1)
                ->addFieldToFilter(
                    'subscriber_status',
                    \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED
                );
        }

        $guestCollection->getSelect()->limit($pageSize, $offset);

        return $guestCollection;
    }

    /**
     * Number contacts marked as imported.
     *
     * @return int
     */
    public function getNumberOfImportedCustomers()
    {
        $this->addFieldToFilter('email_imported', 1);

        return $this->getSize();
    }

    /**
     * Get customers to import by website.
     *
     * @param int $websiteId
     * @param boolean $onlySubscriber
     * @param int $pageSize
     * @param int $offset
     *
     * @return $this
     */
    public function getCustomersToImportByWebsite($websiteId, $onlySubscriber, $pageSize, $offset)
    {
        $collection = $this->addFieldToSelect('*')
            ->addFieldToFilter('customer_id', ['neq' => '0'])
            ->addFieldToFilter('email_imported', 0)
            ->addFieldToFilter('website_id', $websiteId);

        if ($onlySubscriber) {
            $collection->addFieldToFilter('is_subscriber', 1)
                ->addFieldToFilter(
                    'subscriber_status',
                    \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED
                );
        }

        $collection->getSelect()->limit($pageSize, $offset);

        return $collection;
    }

    /**
     * Get customer scope data.
     *
     * @param array $customerIds
     * @param string|int $websiteId
     * @return Collection
     */
    public function getCustomerScopeData(array $customerIds, $websiteId = 0)
    {
        return $this->addFieldToFilter('customer_id', ['in' => $customerIds])
            ->addFieldToFilter('website_id', $websiteId)
            ->addFieldToSelect(['email_contact_id', 'customer_id', 'store_id']);
    }

    /**
     * Get contacts by email and website id.
     *
     * @param array $emails
     * @param string|int $websiteId
     * @return $this
     */
    public function getContactsByEmailsAndWebsiteId($emails, $websiteId)
    {
        return $this->addFieldToFilter('email', ['in' => $emails])
            ->addFieldToFilter('website_id', $websiteId);
    }

    /**
     * Get current subscribed contact records to check when they last subscribed.
     *
     * @param array $emails
     * @param array $websiteIds
     *
     * @return array
     */
    public function getSubscribersWithScopeAndLastSubscribedAtDate(array $emails, $websiteIds)
    {
        return $this
            ->addFieldToSelect([
                'email',
                'last_subscribed_at',
                'website_id',
                'store_id'
            ])
            ->addFieldToFilter('email', ['in' => $emails])
            ->addFieldToFilter('website_id', ['in' => $websiteIds])
            ->addFieldToFilter('is_subscriber', 1)
            ->addFieldToFilter(
                'subscriber_status',
                \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED
            )
            ->getData();
    }

    /**
     * Find matching contacts.
     *
     * @param array $emails
     * @param int $websiteId
     *
     * @return array
     */
    public function matchEmailsToContacts($emails, $websiteId)
    {
        return $this->addFieldToFilter('email', ['in' => $emails])
            ->addFieldToFilter('website_id', $websiteId)
            ->getColumnValues('email');
    }

    /**
     * Get contacts by contact id.
     *
     * @param array $emailContactIds
     *
     * @return $this
     */
    public function getContactsByContactIds($emailContactIds)
    {
        return $this->addFieldToFilter('email_contact_id', ['in' => $emailContactIds]);
    }
}
