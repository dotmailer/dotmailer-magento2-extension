<?php

class Dotdigitalgroup_Email_Block_Customer_Account_Books extends Mage_Customer_Block_Account_Dashboard
{
    private $_client;
    private $contact_id;

    /**
     * subscription pref save url
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('connector/customer_newsletter/save');
    }

    /**
     * get config values
     *
     * @param $path
     * @param $website
     * @return mixed
     */
    private function _getWebsiteConfigFromHelper($path, $website)
    {
        return Mage::helper('ddg')->getWebsiteConfig($path, $website);
    }

    /**
     * get api client
     *
     * @return Dotdigitalgroup_Email_Model_Apiconnector_Client
     */
    private function _getApiClient()
    {
        if(empty($this->_client)) {
            $website = $this->getCustomer()->getStore()->getWebsite();
            $client = Mage::getModel('ddg_automation/apiconnector_client');
            $client->setApiUsername(Mage::helper('ddg')->getApiUsername($website))
                ->setApiPassword(Mage::helper('ddg')->getApiPassword($website));
            $this->_client = $client;
        }
        return $this->_client;
    }

    /**
     * can show additional books?
     *
     * @return mixed
     */
    public function getCanShowAdditionalBooks()
    {
        return $this->_getWebsiteConfigFromHelper(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_CAN_CHANGE_BOOKS,
            $this->getCustomer()->getStore()->getWebsite()
        );
    }

    /**
     * getter for additional books. Fully processed.
     *
     * @return array
     */
    public function getAdditionalBooksToShow()
    {
        $additionalBooksToShow = array();
        $additionalFromConfig =  $this->_getWebsiteConfigFromHelper(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_SHOW_BOOKS,
            $this->getCustomer()->getStore()->getWebsite()
        );

        if(strlen($additionalFromConfig))
        {
            $additionalFromConfig = explode(',', $additionalFromConfig);
            $this->getConnectorContact();
            if($this->contact_id){
                $addressBooks = $this->_getApiClient()->getContactAddressBooks($this->contact_id);
                $processedAddressBooks = array();
                if(is_array($addressBooks)){
                    foreach($addressBooks as $addressBook){
                        $processedAddressBooks[$addressBook->id] = $addressBook->name;
                    }
                }
                foreach($additionalFromConfig as $bookId){
                    $connectorBook = $this->_getApiClient()->getAddressBookById($bookId);
                    if(isset($connectorBook->id))
                    {
                        $subscribed = 0;
                        if(isset($processedAddressBooks[$bookId]))
                            $subscribed = 1;
                        $additionalBooksToShow[] = array(
                            "name"         => $connectorBook->name,
                            "value"         => $connectorBook->id,
                            "subscribed"    => $subscribed
                        );
                    }
                }
            }
        }
        return $additionalBooksToShow;
    }

    /**
     * can show data fields?
     *
     * @return mixed
     */
    public function getCanShowDataFields()
    {
        return $this->_getWebsiteConfigFromHelper(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_CAN_SHOW_FIELDS,
            $this->getCustomer()->getStore()->getWebsite()
        );
    }

    /**
     * getter for data fields to show. Fully processed.
     *
     * @return array
     */
    public function getDataFieldsToShow()
    {
        $datafieldsToShow = array();
        $dataFieldsFromConfig =  $this->_getWebsiteConfigFromHelper(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_SHOW_FIELDS,
            $this->getCustomer()->getStore()->getWebsite()
        );
        if(strlen($dataFieldsFromConfig))
        {
            $dataFieldsFromConfig = explode(',', $dataFieldsFromConfig);
            $contact = $this->getConnectorContact();
            if($this->contact_id)
            {
                $contactDataFields = $contact->dataFields;
                $processedContactDataFields = array();
                foreach($contactDataFields as $contactDataField){
                    $processedContactDataFields[$contactDataField->key] = $contactDataField->value;
                }

                $connectorDataFields = $this->_getApiClient()->getDataFields();
                $processedConnectorDataFields = array();
                foreach($connectorDataFields as $connectorDataField){
                    $processedConnectorDataFields[$connectorDataField->name] = $connectorDataField;
                }
                foreach($dataFieldsFromConfig as $dataFieldFromConfig){
                    if(isset($processedConnectorDataFields[$dataFieldFromConfig])){
                        $value = "";
                        $type = "";
                        if(isset($processedContactDataFields[$processedConnectorDataFields[$dataFieldFromConfig]->name])){
                            if($processedConnectorDataFields[$dataFieldFromConfig]->type == "Date"){
                                $type = "Date";
                                $value = $processedContactDataFields[$processedConnectorDataFields[$dataFieldFromConfig]->name];
                                $value = Mage::app()->getLocale()->date($value)->toString("Y/M/d");
                            }
                            else
                                $value = $processedContactDataFields[$processedConnectorDataFields[$dataFieldFromConfig]->name];
                        }

                        $datafieldsToShow[] = array(
                            'name'  =>  $processedConnectorDataFields[$dataFieldFromConfig]->name,
                            'type'  =>  $processedConnectorDataFields[$dataFieldFromConfig]->type,
                            'value' =>  $value
                        );
                    }
                }

            }
        }
        return $datafieldsToShow;
    }

    /**
     * find out if anything is true
     *
     * @return bool
     */
    public function canShowAnything()
    {
        if($this->getCanShowDataFields() or $this->getCanShowAdditionalBooks()){
            $books = $this->getAdditionalBooksToShow();
            $fields = $this->getDataFieldsToShow();
            if(!empty($books) or !empty($fields))
                return true;
        }
        return false;
    }

    /**
     * get connector contact
     *
     * @return mixed
     */
    public function getConnectorContact()
    {
        $contact = $this->_getApiClient()->getContactByEmail($this->getCustomer()->getEmail());
        if($contact->id){
            $this->contact_id = $contact->id;
        }else{
            $contact = $this->_getApiClient()->postContacts($this->getCustomer()->getEmail());
            if($contact->id){
                $this->contact_id = $contact->id;
            }
        }
        return $contact;
    }

    /**
     * getter for contact id
     *
     * @return mixed
     */
    public function getConnectorContactId()
    {
        return $this->contact_id;
    }
}
