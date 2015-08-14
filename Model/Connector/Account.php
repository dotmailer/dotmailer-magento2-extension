<?php

class Dotdigitalgroup_Email_Model_Connector_Account
{
    private  $_api_username;
    private  $_api_password;
    private  $_limit;
    private  $_contactBookId;
    private  $_subscriberBookId;
    private  $_websites = array();
    private  $_csv_headers;
    private  $_customers_filename;
    private  $_subscribers_filename;
    private  $_mapping_hash;
    private  $_contacts = array();
    private  $_orders = array();
    private  $_orderIds;
    private $_ordersForSingleSync = array();
    private $_orderIdsForSingleSync;

    /**
     * @param $api_password
     * @return $this
     */
    public function setApiPassword($api_password)
    {
        $this->_api_password = $api_password;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getApiPassword()
    {
        return $this->_api_password;
    }

    /**
     * @param $api_username
     * @return $this
     */
    public function setApiUsername($api_username)
    {
        $this->_api_username = $api_username;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getApiUsername()
    {
        return $this->_api_username;
    }

    /**
     * @param string $contactBookId
     */
    public function setContactBookId($contactBookId)
    {
        $this->_contactBookId[$contactBookId] = $contactBookId;
    }

    /**
     * @return array
     */
    public function getContactBookIds()
    {
        return $this->_contactBookId;
    }

    /**
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
     * @return array
     */
    public function getContacts()
    {
        return $this->_contacts;
    }

    /**
     * @param mixed $customers_filename
     */
    public function setCustomersFilename($customers_filename)
    {
        $this->_customers_filename = $customers_filename;
    }

    /**
     * @return mixed
     */
    public function getCustomersFilename()
    {
        return $this->_customers_filename;
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
     * @param mixed $mapping_hash
     */
    public function setMappingHash($mapping_hash)
    {
        $this->_mapping_hash = $mapping_hash;
    }

    /**
     * @return mixed
     */
    public function getMappingHash()
    {
        return $this->_mapping_hash;
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
     * @param mixed $subscribers_filename
     */
    public function setSubscribersFilename($subscribers_filename)
    {
        $this->_subscribers_filename = $subscribers_filename;
    }

    /**
     * @return mixed
     */
    public function getSubscribersFilename()
    {
        return $this->_subscribers_filename;
    }

    /**
     * @param mixed $csv_headers
     */
    public function setCsvHeaders($csv_headers)
    {
        $this->_csv_headers = $csv_headers;
    }

    /**
     * @return mixed
     */
    public function getCsvHeaders()
    {
        return $this->_csv_headers;
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