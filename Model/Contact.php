<?php

namespace Dotdigitalgroup\Email\Model;

class Contact extends \Magento\Framework\Model\AbstractModel
{
    const EMAIL_CONTACT_IMPORTED = 1;
    const EMAIL_CONTACT_NOT_IMPORTED = null;
    const EMAIL_SUBSCRIBER_NOT_IMPORTED = null;

    /**
     * Constructor.
     */
    public function _construct()
    {
        $this->_init('Dotdigitalgroup\Email\Model\ResourceModel\Contact');
    }

    /**
     * Load contact by customer id.
     *
     * @param int $customerId
     *
     * @return mixed
     */
    public function loadByCustomerId($customerId)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->setPageSize(1);

        if ($collection->getSize()) {
            //@codingStandardsIgnoreStart
            return $collection->getFirstItem();
            //@codingStandardsIgnoreEnd
        }

        return $this;
    }

    /**
     * Get all customer contacts not imported for a website.
     *
     * @param int $websiteId
     * @param int $pageSize
     *
     * @return $this
     */
    public function getContactsToImportForWebsite($websiteId, $pageSize = 100)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('website_id', $websiteId)
            ->addFieldToFilter('email_imported', ['null' => true])
            ->addFieldToFilter('customer_id', ['neq' => '0']);

        $collection->getSelect()->limit($pageSize);

        return $collection;
    }

    /**
     * Get missing contacts.
     *
     * @param int $websiteId
     * @param int $pageSize
     *
     * @return mixed
     */
    public function getMissingContacts($websiteId, $pageSize = 100)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('contact_id', ['null' => true])
            ->addFieldToFilter('suppressed', ['null' => true])
            ->addFieldToFilter('website_id', $websiteId);

        $collection->getSelect()->limit($pageSize);

        return $collection->load();
    }

    /**
     * Load Contact by Email.
     *
     * @param string $email
     * @param int $websiteId
     *
     * @return $this
     */
    public function loadByCustomerEmail($email, $websiteId)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('email', $email)
            ->addFieldToFilter('website_id', $websiteId)
            ->setPageSize(1);

        if ($collection->getSize()) {
            //@codingStandardsIgnoreStart
            return $collection->getFirstItem();
            //@codingStandardsIgnoreEnd
        } else {
            $this->setEmail($email)
                ->setWebsiteId($websiteId);
        }

        return $this;
    }

    /**
     * Contact subscribers to import for website.
     *
     * @param \Magento\Store\Model\Website $website
     * @param int $limit
     *
     * @return $this
     */
    public function getSubscribersToImport(
        \Magento\Store\Model\Website $website,
        $limit = 1000,
        $isCustomerCheck = true
    ) {
        $storeIds = $website->getStoreIds();
        $collection = $this->getCollection()
            ->addFieldToFilter('is_subscriber', ['notnull' => true])
            ->addFieldToFilter('subscriber_status', '1')
            ->addFieldToFilter('subscriber_imported', ['null' => true])
            ->addFieldToFilter('store_id', ['in' => $storeIds]);

        if ($isCustomerCheck) {
            $collection->addFieldToFilter('customer_id', ['neq' => 0]);
        } else {
            $collection->addFieldToFilter('customer_id', ['eq' => 0]);
        }

        $collection->getSelect()->limit($limit);

        return $collection;
    }

    /**
     * Contact subscribers to import for website.
     *
     * @param $emails
     *
     * @return $this
     */
    public function getSubscribersToImportFromEmails($emails)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('email', ['in' => $emails]);
        return $collection;
    }

    /**
     * Get all not imported guests for a website.
     *
     * @param \Magento\Store\Model\Website $website
     *
     * @return $this
     */
    public function getGuests(\Magento\Store\Model\Website $website)
    {
        $guestCollection = $this->getCollection()
            ->addFieldToFilter('is_guest', ['notnull' => true])
            ->addFieldToFilter('email_imported', ['null' => true])
            ->addFieldToFilter('website_id', $website->getId());

        return $guestCollection->load();
    }

    /**
     * Number contacts marked as imported.
     *
     * @return int
     */
    public function getNumberOfImportedContacs()
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('email_imported', ['notnull' => true]);

        return $collection->getSize();
    }

    /**
     * Get the number of customers for a website.
     *
     * @param int $websiteId
     *
     * @return int
     */
    public function getNumberCustomerContacts($websiteId = 0)
    {
        $countContacts = $this->getCollection()
            ->addFieldToFilter('customer_id', ['gt' => '0'])
            ->addFieldToFilter('website_id', $websiteId)
            ->getSize();

        return $countContacts;
    }

    /**
     * Get number of suppressed contacts as customer.
     *
     * @param int $websiteId
     *
     * @return int
     */
    public function getNumberCustomerSuppressed($websiteId = 0)
    {
        $countContacts = $this->getCollection()
            ->addFieldToFilter('customer_id', ['gt' => 0])
            ->addFieldToFilter('website_id', $websiteId)
            ->addFieldToFilter('suppressed', '1')
            ->getSize();

        return $countContacts;
    }

    /**
     * Get number of synced customers.
     *
     * @param int $websiteId
     *
     * @return int
     */
    public function getNumberCustomerSynced($websiteId = 0)
    {
        $countContacts = $this->getCollection()
            ->addFieldToFilter('customer_id', ['gt' => 0])
            ->addFieldToFilter('website_id', $websiteId)
            ->addFieldToFilter('email_imported', '1')
            ->getSize();

        return $countContacts;
    }

    /**
     * Get number of subscribers synced.
     *
     * @param int $websiteId
     *
     * @return int
     */
    public function getNumberSubscribersSynced($websiteId = 0)
    {
        $countContacts = $this->getCollection()
            ->addFieldToFilter(
                'subscriber_status',
                \Dotdigitalgroup\Email\Model\Newsletter\Subscriber::STATUS_SUBSCRIBED
            )
            ->addFieldToFilter('subscriber_imported', '1')
            ->addFieldToFilter('website_id', $websiteId)
            ->getSize();

        return $countContacts;
    }

    /**
     * Get number of subscribers.
     *
     * @param int $websiteId
     *
     * @return int
     */
    public function getNumberSubscribers($websiteId = 0)
    {
        $countContacts = $this->getCollection()
            ->addFieldToFilter(
                'subscriber_status',
                \Dotdigitalgroup\Email\Model\Newsletter\Subscriber::STATUS_SUBSCRIBED
            )
            ->addFieldToFilter('website_id', $websiteId)
            ->getSize();

        return $countContacts;
    }
}
