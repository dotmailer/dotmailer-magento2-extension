<?php

namespace Dotdigitalgroup\Email\Model\Connector;

class Account
{
    /**
     * @var
     */
    public $apiUsername;
    /**
     * @var
     */
    public $apiPassword;
    /**
     * @var
     */
    public $limit;
    /**
     * @var
     */
    public $contactBookId;
    /**
     * @var
     */
    public $subscriberBookId;
    /**
     * @var array
     */
    public $websites = [];
    /**
     * @var
     */
    public $csvHeaders;
    /**
     * @var
     */
    public $customersFilename;
    /**
     * @var
     */
    public $subscribersFilename;
    /**
     * @var
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
     * @var
     */
    public $orderIds;
    /**
     * @var array
     */
    public $ordersForSingleSync = [];
    /**
     * @var
     */
    public $orderIdsForSingleSync;

    /**
     * Set api password.
     *
     * @param $apiPassword
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
     * @param $apiUsername
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
     * @param $customersFilename
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
     * @param $mappingHash
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
     * @param $subscribersFilename
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
     * @param $csvHeaders
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
