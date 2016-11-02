<?php

namespace Dotdigitalgroup\Email\Model\Connector;

class Account
{
    /**
     * @var
     */
    protected $_apiUsername;
    /**
     * @var
     */
    protected $_apiPassword;
    /**
     * @var
     */
    protected $_limit;
    /**
     * @var
     */
    protected $_contactBookId;
    /**
     * @var
     */
    protected $_subscriberBookId;
    /**
     * @var array
     */
    protected $_websites = [];
    /**
     * @var
     */
    protected $_csvHeaders;
    /**
     * @var
     */
    protected $_customersFilename;
    /**
     * @var
     */
    protected $_subscribersFilename;
    /**
     * @var
     */
    protected $_mappingHash;
    /**
     * @var array
     */
    protected $_contacts = [];
    /**
     * @var array
     */
    protected $_orders = [];
    /**
     * @var
     */
    protected $_orderIds;
    /**
     * @var array
     */
    protected $_ordersForSingleSync = [];
    /**
     * @var
     */
    protected $_orderIdsForSingleSync;

    /**
     * Set api password.
     *
     * @param $apiPassword
     *
     * @return $this
     */
    public function setApiPassword($apiPassword)
    {
        $this->_apiPassword = $apiPassword;

        return $this;
    }

    /**
     * Get api password.
     *
     * @return mixed
     */
    public function getApiPassword()
    {
        return $this->_apiPassword;
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
        $this->_apiUsername = $apiUsername;

        return $this;
    }

    /**
     * Get api username.
     *
     * @return mixed
     */
    public function getApiUsername()
    {
        return $this->_apiUsername;
    }

    /**
     * Set contact book id.
     *
     * @param string $contactBookId
     */
    public function setContactBookId($contactBookId)
    {
        $this->_contactBookId[$contactBookId] = $contactBookId;
    }

    /**
     * Get contact book ids.
     *
     * @return array
     */
    public function getContactBookIds()
    {
        return $this->_contactBookId;
    }

    /**
     * Set contacts.
     *
     * @param array $contacts
     */
    public function setContacts($contacts)
    {
        if (!empty($this->_contacts)) {
            $this->_contacts += $contacts;
        } else {
            $this->_contacts[] = $contacts;
        }
    }

    /**
     * Get contacts.
     *
     * @return array
     */
    public function getContacts()
    {
        return $this->_contacts;
    }

    /**
     * Set customers filename.
     *
     * @param $customersFilename
     */
    public function setCustomersFilename($customersFilename)
    {
        $this->_customersFilename = $customersFilename;
    }

    /**
     * @return mixed
     */
    public function getCustomersFilename()
    {
        return $this->_customersFilename;
    }

    /**
     * @param mixed $limit
     */
    public function setLimit($limit)
    {
        $this->_limit = $limit;
    }

    /**
     * @return mixed
     */
    public function getLimit()
    {
        return $this->_limit;
    }

    /**
     * @param $mappingHash
     */
    public function setMappingHash($mappingHash)
    {
        $this->_mappingHash = $mappingHash;
    }

    /**
     * @return mixed
     */
    public function getMappingHash()
    {
        return $this->_mappingHash;
    }

    /**
     * @param array $orders
     */
    public function setOrders($orders)
    {
        foreach ($orders as $order) {
            $this->_orders[$order->id] = $order;
        }
    }

    /**
     * @return array
     */
    public function getOrders()
    {
        return $this->_orders;
    }

    /**
     * @param string $subscriberBookId
     */
    public function setSubscriberBookId($subscriberBookId)
    {
        $this->_subscriberBookId[$subscriberBookId] = $subscriberBookId;
    }

    /**
     * @return array
     */
    public function getSubscriberBookIds()
    {
        return $this->_subscriberBookId;
    }

    /**
     * @param $subscribersFilename
     */
    public function setSubscribersFilename($subscribersFilename)
    {
        $this->_subscribersFilename = $subscribersFilename;
    }

    /**
     * @return mixed
     */
    public function getSubscribersFilename()
    {
        return $this->_subscribersFilename;
    }

    /**
     * @param $csvHeaders
     */
    public function setCsvHeaders($csvHeaders)
    {
        $this->_csvHeaders = $csvHeaders;
    }

    /**
     * @return mixed
     */
    public function getCsvHeaders()
    {
        return $this->_csvHeaders;
    }

    /**
     * @param mixed $websites
     */
    public function setWebsites($websites)
    {
        $this->_websites[] = $websites;
    }

    /**
     * @return mixed
     */
    public function getWebsites()
    {
        return $this->_websites;
    }

    /**
     * @param array $orderIds
     */
    public function setOrderIds($orderIds)
    {
        $this->_orderIds = $orderIds;
    }

    /**
     * @return array
     */
    public function getOrderIds()
    {
        return $this->_orderIds;
    }

    /**
     * @param array $orders
     */
    public function setOrdersForSingleSync($orders)
    {
        foreach ($orders as $order) {
            $this->_ordersForSingleSync[$order->id] = $order;
        }
    }

    /**
     * @return array
     */
    public function getOrdersForSingleSync()
    {
        return $this->_ordersForSingleSync;
    }

    /**
     * @param array $orderIds
     */
    public function setOrderIdsForSingleSync($orderIds)
    {
        $this->_orderIdsForSingleSync = $orderIds;
    }

    /**
     * @return array
     */
    public function getOrderIdsForSingleSync()
    {
        return $this->_orderIdsForSingleSync;
    }
}
