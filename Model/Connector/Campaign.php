<?php

class Dotdigitalgroup_Email_Model_Connector_Campaign
{

    public $id;
    public $contacts = array();
    public $emails = array();
    public $emailSendId = array();
    private $_storeId;

    /**
     * @param mixed $storeId
     */
    public function setStoreId($storeId)
    {
        $this->_storeId = $storeId;
    }

    /**
     * @return mixed
     */
    public function getStoreId()
    {
        return $this->_storeId;
    }

    /**
     * @param $emailSendId
     * @return $this
     */
    public function setEmailSendId($emailSendId)
    {
        $this->emailSendId[] = $emailSendId;
        return $this;
    }

    /**
     * @return array
     */
    public function getEmailSendId()
    {
        return $this->emailSendId;
    }

    /**
     * @param $contact
     * @return $this
     */
    public function setContactId($contact)
    {
        $this->contacts[] = $contact;
        return $this;
    }

    /**
     * @return array
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * @param $emails
     * @return $this
     */
    public function setEmails($emails)
    {
        $this->emails = $emails;
        return $this;
    }

    /**
     * @return array
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

}
