<?php

namespace Dotdigitalgroup\Email\Model\Connector;

/**
 * Holds Account information.
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class Account
{
    /**
     * @var string
     */
    public $apiUsername;

    /**
     * @var string
     */
    public $apiPassword;

    /**
     * @var int|string
     */
    public $limit;

    /**
     * @var int|string
     */
    public $contactBookId;

    /**
     * @var int|string
     */
    public $subscriberBookId;

    /**
     * @var array
     */
    public $websites = [];

    /**
     * @var array
     */
    public $csvHeaders;

    /**
     * @var string
     */
    public $customersFilename;

    /**
     * @var string
     */
    public $subscribersFilename;

    /**
     * @var array
     */
    public $mappingHash;

    /**
     * @var array
     */
    public $contacts = [];

    /**
     * @var array
     */
    public $orders = [];

    /**
     * @var array
     */
    public $orderIds;

    /**
     * @var array
     */
    public $ordersForSingleSync = [];

    /**
     * @var array
     */
    public $orderIdsForSingleSync;

    /**
     * Set api password.
     *
     * @param string $apiPassword
     *
     * @return $this
     */
    public function setApiPassword($apiPassword)
    {
        $this->apiPassword = $apiPassword;

        return $this;
    }

    /**
     * Get api password.
     *
     * @return string
     */
    public function getApiPassword()
    {
        return $this->apiPassword;
    }

    /**
     * Set api username.
     *
     * @param string $apiUsername
     *
     * @return $this
     */
    public function setApiUsername($apiUsername)
    {
        $this->apiUsername = $apiUsername;

        return $this;
    }

    /**
     * Get api username.
     *
     * @return string
     */
    public function getApiUsername()
    {
        return $this->apiUsername;
    }

    /**
     * Set contact book id.
     *
     * @param string $contactBookId
     *
     * @return null
     */
    public function setContactBookId($contactBookId)
    {
        $this->contactBookId[$contactBookId] = $contactBookId;
    }

    /**
     * Get contact book ids.
     *
     * @return string
     */
    public function getContactBookIds()
    {
        return $this->contactBookId;
    }

    /**
     * Set contacts.
     *
     * @param array $contacts
     *
     * @return null
     */
    public function setContacts($contacts)
    {
        if (!empty($this->contacts)) {
            $this->contacts += $contacts;
        } else {
            $this->contacts[] = $contacts;
        }
    }

    /**
     * Get contacts.
     *
     * @return array
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * Set customers filename.
     *
     * @param string $customersFilename
     *
     * @return null
     */
    public function setCustomersFilename($customersFilename)
    {
        $this->customersFilename = $customersFilename;
    }

    /**
     * @return string
     */
    public function getCustomersFilename()
    {
        return $this->customersFilename;
    }

    /**
     * @param int $limit
     *
     * @return string
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * @return string
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param array $mappingHash
     */
    public function setMappingHash($mappingHash)
    {
        $this->mappingHash = $mappingHash;
    }

    /**
     * @return array
     */
    public function getMappingHash()
    {
        return $this->mappingHash;
    }

    /**
     * @param array $orders
     *
     * @return array
     */
    public function setOrders($orders)
    {
        foreach ($orders as $order) {
            $this->orders[$order->id] = $order->expose();
        }
    }

    /**
     * @return array
     */
    public function getOrders()
    {
        return $this->orders;
    }

    /**
     * @param string $subscriberBookId
     *
     * @return array
     */
    public function setSubscriberBookId($subscriberBookId)
    {
        $this->subscriberBookId[$subscriberBookId] = $subscriberBookId;
    }

    /**
     * @return string
     */
    public function getSubscriberBookIds()
    {
        return $this->subscriberBookId;
    }

    /**
     * @param string $subscribersFilename
     *
     * @return string
     */
    public function setSubscribersFilename($subscribersFilename)
    {
        $this->subscribersFilename = $subscribersFilename;
    }

    /**
     * @return string
     */
    public function getSubscribersFilename()
    {
        return $this->subscribersFilename;
    }

    /**
     * @param array $csvHeaders
     *
     * @return array
     */
    public function setCsvHeaders($csvHeaders)
    {
        $this->csvHeaders = $csvHeaders;
    }

    /**
     * @return array
     */
    public function getCsvHeaders()
    {
        return $this->csvHeaders;
    }

    /**
     * @param array $websites
     *
     * @return array
     */
    public function setWebsites($websites)
    {
        $this->websites[] = $websites;
    }

    /**
     * @return array
     */
    public function getWebsites()
    {
        return $this->websites;
    }

    /**
     * @param array $orderIds
     *
     * @return array
     */
    public function setOrderIds($orderIds)
    {
        $this->orderIds = $orderIds;
    }

    /**
     * @return array
     */
    public function getOrderIds()
    {
        return $this->orderIds;
    }

    /**
     * @param array $orders
     *
     * @return null
     */
    public function setOrdersForSingleSync($orders)
    {
        foreach ($orders as $order) {
            $this->ordersForSingleSync[$order->id] = $order->expose();
        }
    }

    /**
     * @return array
     */
    public function getOrdersForSingleSync()
    {
        return $this->ordersForSingleSync;
    }

    /**
     * @param array $orderIds
     *
     * @return null
     */
    public function setOrderIdsForSingleSync($orderIds)
    {
        $this->orderIdsForSingleSync = $orderIds;
    }

    /**
     * @return array
     */
    public function getOrderIdsForSingleSync()
    {
        return $this->orderIdsForSingleSync;
    }
}
