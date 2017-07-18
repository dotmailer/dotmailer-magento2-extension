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
     * @var object
     */
    public $apiUsername;

    /**
     * @var object
     */
    public $apiPassword;

    /**
     * @var object
     */
    public $limit;

    /**
     * @var object
     */
    public $contactBookId;

    /**
     * @var object
     */
    public $subscriberBookId;

    /**
     * @var array
     */
    public $websites = [];

    /**
     * @var object
     */
    public $csvHeaders;

    /**
     * @var object
     */
    public $customersFilename;

    /**
     * @var object
     */
    public $subscribersFilename;

    /**
     * @var object
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
     * @var object
     */
    public $orderIds;

    /**
     * @var array
     */
    public $ordersForSingleSync = [];

    /**
     * @var object
     */
    public $orderIdsForSingleSync;

    /**
     * Set api password.
     *
     * @param mixed $apiPassword
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
     * @return mixed
     */
    public function getApiPassword()
    {
        return $this->apiPassword;
    }

    /**
     * Set api username.
     *
     * @param mixed $apiUsername
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
     * @return mixed
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
     * @return array
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
     * @param mixed $customersFilename
     * 
     * @return null
     */
    public function setCustomersFilename($customersFilename)
    {
        $this->customersFilename = $customersFilename;
    }

    /**
     * @return mixed
     */
    public function getCustomersFilename()
    {
        return $this->customersFilename;
    }

    /**
     * @param mixed $limit
     * 
     * @return null
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * @return mixed
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param mixed $mappingHash
     * 
     * @return null
     */
    public function setMappingHash($mappingHash)
    {
        $this->mappingHash = $mappingHash;
    }

    /**
     * @return mixed
     */
    public function getMappingHash()
    {
        return $this->mappingHash;
    }

    /**
     * @param array $orders
     * 
     * @return null
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
     * @return null
     */
    public function setSubscriberBookId($subscriberBookId)
    {
        $this->subscriberBookId[$subscriberBookId] = $subscriberBookId;
    }

    /**
     * @return array
     */
    public function getSubscriberBookIds()
    {
        return $this->subscriberBookId;
    }

    /**
     * @param mixed $subscribersFilename
     * 
     * @return null
     */
    public function setSubscribersFilename($subscribersFilename)
    {
        $this->subscribersFilename = $subscribersFilename;
    }

    /**
     * @return mixed
     */
    public function getSubscribersFilename()
    {
        return $this->subscribersFilename;
    }

    /**
     * @param mixed $csvHeaders
     * 
     * @return null
     */
    public function setCsvHeaders($csvHeaders)
    {
        $this->csvHeaders = $csvHeaders;
    }

    /**
     * @return mixed
     */
    public function getCsvHeaders()
    {
        return $this->csvHeaders;
    }

    /**
     * @param mixed $websites
     * 
     * @return null
     */
    public function setWebsites($websites)
    {
        $this->websites[] = $websites;
    }

    /**
     * @return mixed
     */
    public function getWebsites()
    {
        return $this->websites;
    }

    /**
     * @param array $orderIds
     * 
     * @return null
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
