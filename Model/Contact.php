<?php

namespace Dotdigitalgroup\Email\Model;

class Contact extends \Magento\Framework\Model\AbstractModel
{
    const EMAIL_CONTACT_IMPORTED = 1;
    const EMAIL_CONTACT_NOT_IMPORTED = null;
    const EMAIL_SUBSCRIBER_NOT_IMPORTED = null;

    /**
     * Constructor.
     *
     * @return null
     */
    public function _construct()
    {
        $this->_init(\Dotdigitalgroup\Email\Model\ResourceModel\Contact::class);
    }

    /**
     * Load contact by customer id.
     *
     * @param int $customerId
     *
     * @return $this
     */
    public function loadByCustomerId($customerId)
    {
        $contact = $this->getCollection()
            ->loadByCustomerId($customerId);

        if ($contact) {
            return $contact;
        }

        return $this;
    }

    /**
     * Get all customer contacts not imported for a website.
     *
     * @param int $websiteId
     * @param int $pageSize
     *
     * @return \Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection
     */
    public function getContactsToImportForWebsite($websiteId, $pageSize = 100)
    {
        return $this->getCollection()
            ->getContactsToImportForWebsite($websiteId, $pageSize);
    }

    /**
     * Get missing contacts.
     *
     * @param int $websiteId
     * @param int $pageSize
     *
     * @return \Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection
     */
    public function getMissingContacts($websiteId, $pageSize = 100)
    {
        return $this->getCollection()
            ->getMissingContacts($websiteId, $pageSize);
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
        $customer = $this->getCollection()
            ->loadByCustomerEmail($email, $websiteId);

        if ($customer) {
            return $customer;
        } else {
            return $this->setEmail($email)
                ->setWebsiteId($websiteId);
        }
    }

    /**
     * Contact subscribers to import for store.
     *
     * @param int $storeId
     * @param int $limit
     * @param bool $isCustomerCheck
     *
     * @return \Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection
     */
    public function getSubscribersToImport(
        $storeId,
        $limit = 1000,
        $isCustomerCheck = true
    ) {
        return $this->getCollection()
            ->getSubscribersToImport(
                $storeId,
                $limit,
                $isCustomerCheck
            );
    }

    /**
     * Contact subscribers to import for website.
     *
     * @param array $emails
     *
     * @return \Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection
     */
    public function getSubscribersToImportFromEmails($emails)
    {
        return $this->getCollection()
            ->getSubscribersToImportFromEmails($emails);
    }

    /**
     * Get all not imported guests for a website.
     *
     * @param \Magento\Store\Model\Website $website
     * @param boolean $onlySubscriber
     *
     * @return \Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection
     */
    public function getGuests($website, $onlySubscriber = false)
    {
        return $this->getCollection()
            ->getGuests($website->getId(), $onlySubscriber);
    }

    /**
     * Number contacts marked as imported.
     *
     * @return int
     */
    public function getNumberOfImportedContacs()
    {
        return $this->getCollection()
            ->getNumberOfImportedContacts();
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
        return $this->getCollection()
            ->getNumberCustomerContacts($websiteId);
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
        return $this->getCollection()
            ->getNumberCustomerSuppressed($websiteId);
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
        return $this->getCollection()
            ->getNumberCustomerSynced($websiteId);
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
        return $this->getCollection()
            ->getNumberSubscribersSynced($websiteId);
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
        return $this->getCollection()
            ->getNumberSubscribers($websiteId);
    }
}
