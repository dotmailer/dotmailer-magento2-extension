<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Observer
{

    /**
     * API Sync and Data Mapping.
     * Reset contacts for reimport.
     * @return $this
     */
    public function actionConfigResetContacts()
    {
        $contactModel = Mage::getModel('ddg_automation/contact');
        $numImported = $contactModel->getNumberOfImportedContacs();
        $updated = $contactModel->resetAllContacts();
        Mage::helper('ddg')->log('-- Imported contacts: ' . $numImported  . ' reseted :  ' . $updated . ' --');

        /**
         * check for addressbook mapping and disable if no address selected.
         */
        $this->_checkAddressBookMapping(Mage::app()->getRequest()->getParam('website'));

        return $this;
    }

    /**
     * Check if the transactional data feature is enabled
     * To use the wishlist and order sync this needs to be enabled.
     */
    public function checkFeatureActive()
    {
        //scope to retrieve the website id
        $scopeId = 0;
        if ($website = Mage::app()->getRequest()->getParam('website')) {
            //use webiste
            $scope = 'websites';
            $scopeId = Mage::app()->getWebsite($website)->getId();
        } else {
            //set to default
            $scope = "default";
        }
        //webiste by id
        $website = Mage::app()->getWebsite($scopeId);

        //configuration saved for the wishlist and order sync
        $wishlistEnabled = $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_WISHLIST_ENABLED, $scope, $scopeId);
        $orderEnabled = $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED);

        //only for modification for order and wishlist
        if ($orderEnabled || $wishlistEnabled) {
            //client by website id
            $client = Mage::helper('ddg')->getWebsiteApiClient($scopeId);

            //call request for account info
            $response = $client->getAccountInfo();

            //properties must be checked
            if (isset($response->properties)) {
                $accountInfo = $response->properties;
                $result = $this->_checkForOption(Dotdigitalgroup_Email_Model_Apiconnector_Client::API_ERROR_TRANS_ALLOWANCE, $accountInfo);

                //account is disabled to use transactional data
                if (! $result) {
                    $message = 'Transactional Data For This Account Is Disabled. Call Support To Enable.';
                    //send admin message
                    Mage::getSingleton('adminhtml/session')->addError($message);

                    //send raygun message for trans data
                    Mage::helper('ddg')->rayLog('100', $message);
                    //disable the config for wishlist and order sync
                    $config = Mage::getConfig();
                    $config->saveConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_WISHLIST_ENABLED, 0, $scope, $scopeId);
                    $config->saveConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED, 0, $scope, $scopeId);
                    $config->cleanCache();
                }
            }
        }

        return $this;

    }

    /**
     * API Credentials.
     * Installation and validation confirmation.
     * @return $this
     */
    public function actionConfigSaveApi()
    {
        $groups = Mage::app()->getRequest()->getPost('groups');
        if (isset($groups['api']['fields']['username']['inherit']) || isset($groups['api']['fields']['password']['inherit']))
            return $this;

        $apiUsername =  isset($groups['api']['fields']['username']['value'])? $groups['api']['fields']['username']['value'] : false;
        $apiPassword =  isset($groups['api']['fields']['password']['value'])? $groups['api']['fields']['password']['value'] : false;
        //skip if the inherit option is selected
        if ($apiUsername && $apiPassword) {
            Mage::helper('ddg')->log('----VALIDATING ACCOUNT---');
            $testModel = Mage::getModel('ddg_automation/apiconnector_test');
            $isValid = $testModel->validate($apiUsername, $apiPassword);
            if ($isValid) {
                /**
                 * Send install info
                 */
                //$testModel->sendInstallConfirmation();
            } else {
                /**
                 * Disable invalid Api credentials
                 */
                $scopeId = 0;
                if ($website = Mage::app()->getRequest()->getParam('website')) {
                    $scope = 'websites';
                    $scopeId = Mage::app()->getWebsite($website)->getId();
                } else {
                    $scope = "default";
                }
                $config = Mage::getConfig();
                $config->saveConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED, 0, $scope, $scopeId);
                $config->cleanCache();
            }
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('ddg')->__('API Credentials Valid.'));
        }
        return $this;
    }

    private function _checkAddressBookMapping( $website ) {

        $helper = Mage::helper('ddg');
        $customerAddressBook = $helper->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMERS_ADDRESS_BOOK_ID, $website);
        $subscriberAddressBook = $helper->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID, $website);

        if (! $customerAddressBook && $helper->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_CONTACT_ENABLED, $website)){

            $helper->disableConfigForWebsite(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_CONTACT_ENABLED);
            Mage::getSingleton('adminhtml/session')->addNotice('The Contact Sync Disabled - No Addressbook Selected !');
        }
        if (! $subscriberAddressBook && $helper->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_SUBSCRIBER_ENABLED, $website)) {
            $helper->disableConfigForWebsite( Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_SUBSCRIBER_ENABLED );
            Mage::getSingleton('adminhtml/session')->addNotice('The Subscriber Sync Disabled - No Addressbook Selected !');
        }

    }

    /**
     * Check for name option in array.
     *
     * @param $name
     * @param $data
     *
     * @return bool
     */
    private function _checkForOption($name, $data) {
        //loop for all options
        foreach ( $data as $one ) {

            if ($one->name == $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update Feed for latest releases.
     *
     */
    public function updateFeed()
    {
        Mage::getModel('ddg_automation/feed')->checkForUpgrade();
    }


	/**
	 * Add modified segment for contact.
	 * @param $observer
	 *
	 * @return $this
	 */
	public function connectorCustomerSegmentChanged($observer)
	{
		$segmentsIds = $observer->getEvent()->getSegmentIds();
		$customerId = Mage::getSingleton('customer/session')->getCustomerId();
		$websiteId = Mage::app()->getStore()->getWebsiteId();

		if (!empty($segmentsIds) && $customerId) {
			$this->addContactsFromWebsiteSegments($customerId, $segmentsIds, $websiteId);
		}

		return $this;
	}


	/**
	 * Add segment ids.
	 * @param $customerId
	 * @param $segmentIds
	 * @param $websiteId
	 *
	 * @return $this
	 */
	protected function addContactsFromWebsiteSegments($customerId, $segmentIds, $websiteId){

		if (empty($segmentIds))
			return;
		$segmentIds = implode(',', $segmentIds);

		$contact = Mage::getModel('ddg_automation/contact')->getCollection()
			->addFieldToFilter('customer_id', $customerId)
			->addFieldToFilter('website_id', $websiteId)
			->getFirstItem();
		try {

			$contact->setSegmentIds($segmentIds)
			        ->setEmailImported()
			        ->save();

		}catch (Exception $e){
			Mage::logException($e);
		}

		return $this;
	}

	protected function getCustomerSegmentIdsForWebsite($customerId, $websiteId){
		$segmentIds = Mage::getModel('ddg_automation/contact')->getCollection()
			->addFieldToFilter('website_id', $websiteId)
			->addFieldToFilter('customer_id', $customerId)
			->getFirstItem()
			->getSegmentIds();

		return $segmentIds;
	}
}