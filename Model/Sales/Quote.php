<?php

class Dotdigitalgroup_Email_Model_Sales_Quote
{
    //customer
    const XML_PATH_LOSTBASKET_CUSTOMER_ENABLED_1        = 'connector_lost_baskets/customers/enabled_1';
    const XML_PATH_LOSTBASKET_CUSTOMER_ENABLED_2        = 'connector_lost_baskets/customers/enabled_2';
    const XML_PATH_LOSTBASKET_CUSTOMER_ENABLED_3        = 'connector_lost_baskets/customers/enabled_3';
    const XML_PATH_LOSTBASKET_CUSTOMER_INTERVAL_1       = 'connector_lost_baskets/customers/send_after_1';
    const XML_PATH_LOSTBASKET_CUSTOMER_INTERVAL_2       = 'connector_lost_baskets/customers/send_after_2';
    const XML_PATH_LOSTBASKET_CUSTOMER_INTERVAL_3       = 'connector_lost_baskets/customers/send_after_3';
    const XML_PATH_LOSTBASKET_CUSTOMER_CAMPAIGN_1       = 'connector_lost_baskets/customers/campaign_1';
    const XML_PATH_LOSTBASKET_CUSTOMER_CAMPAIGN_2       = 'connector_lost_baskets/customers/campaign_2';
    const XML_PATH_LOSTBASKET_CUSTOMER_CAMPAIGN_3       = 'connector_lost_baskets/customers/campaign_3';

    //guest
    const XML_PATH_LOSTBASKET_GUEST_ENABLED_1           = 'connector_lost_baskets/guests/enabled_1';
    const XML_PATH_LOSTBASKET_GUEST_ENABLED_2           = 'connector_lost_baskets/guests/enabled_2';
    const XML_PATH_LOSTBASKET_GUEST_ENABLED_3           = 'connector_lost_baskets/guests/enabled_3';
    const XML_PATH_LOSTBASKET_GUEST_INTERVAL_1          = 'connector_lost_baskets/guests/send_after_1';
    const XML_PATH_LOSTBASKET_GUEST_INTERVAL_2          = 'connector_lost_baskets/guests/send_after_2';
    const XML_PATH_LOSTBASKET_GUEST_INTERVAL_3          = 'connector_lost_baskets/guests/send_after_3';
    const XML_PATH_LOSTBASKET_GUEST_CAMPAIGN_1          = 'connector_lost_baskets/guests/campaign_1';
    const XML_PATH_LOSTBASKET_GUEST_CAMPAIGN_2          = 'connector_lost_baskets/guests/campaign_2';
    const XML_PATH_LOSTBASKET_GUEST_CAMPAIGN_3          = 'connector_lost_baskets/guests/campaign_3';


	/**
	 * number of lost baskets available.
	 * @var array
	 */
	public $lostBasketCustomers = array(1, 2, 3);
	/**
	 * number of guest lost baskets available.
	 * @var array
	 */
	public $lostBasketGuests = array(1, 2, 3);


	/**
	 * Proccess abandoned carts.
	 *
	 * @param string $mode
	 */
    public function proccessAbandonedCarts($mode = 'all')
    {
        /**
         * Save lost baskets to be send in Send table.
         */
	    $locale = Mage::app()->getLocale()->getLocale();

	    foreach (Mage::app()->getStores() as $store) {
            $storeId = $store->getId();

		    if ($mode == 'all' || $mode == 'customers') {
			    /**
			     * Customers campaings
			     */
			    foreach ( $this->lostBasketCustomers as $num ) {
				    //customer enabled
				    if ( $this->_getLostBasketCustomerEnabled( $num, $storeId ) ) {

					    //number of the campaign use minutes
					    if ( $num == 1 ) {
						    $from = Zend_Date::now( $locale )->subMinute( $this->_getLostBasketCustomerInterval( $num, $storeId ) );
						    //other use hours
					    } else {
						    $from = Zend_Date::now( $locale )->subHour( $this->_getLostBasketCustomerInterval( $num, $storeId ) );
					    }

                        $to = clone($from);
                        $from->sub('5', Zend_Date::MINUTE);

					    //active quotes
					    $quoteCollection = $this->_getStoreQuotes( $from->toString( 'YYYY-MM-dd HH:mm' ), $to->toString( 'YYYY-MM-dd HH:mm' ), $guest = false, $storeId );

					    if ( $quoteCollection->getSize() ) {
						    Mage::helper( 'ddg' )->log( 'Customer lost baskets : ' . $num . ', from : ' . $from->toString( 'YYYY-MM-dd HH:mm' ) . ':' . $to->toString( 'YYYY-MM-dd HH:mm' ) );
					    }

					    //campaign id for customers
					    $campaignId = $this->_getLostBasketCustomerCampaignId( $num, $storeId );
					    foreach ( $quoteCollection as $quote ) {

						    $email        = $quote->getCustomerEmail();
						    $websiteId    = $store->getWebsiteId();
						    $quoteId      = $quote->getId();
						    // upate last quote id for the contact
						    Mage::helper('ddg')->updateLastQuoteId($quoteId, $email, $websiteId);

                            // update abandoned product name for contact
                            $items = $quote->getAllItems();
                            $mostExpensiveItem = false;
                            foreach ($items as $item) {
                                /** @var $item Mage_Sales_Model_Quote_Item */
                                if ($mostExpensiveItem == false)
                                    $mostExpensiveItem = $item;
                                elseif ($item->getPrice() > $mostExpensiveItem->getPrice())
                                    $mostExpensiveItem = $item;
                            }
                            if ($mostExpensiveItem)
                                Mage::helper('ddg')->updateAbandonedProductName($mostExpensiveItem->getName(), $email, $websiteId);

						    //send email only if the interval limit passed, no emails during this interval
						    $campignFound = $this->_checkCustomerCartLimit( $email, $storeId );

						    //no campign found for interval pass
                            if (!$campignFound) {

							    //save lost basket for sending
							    $sendModel = Mage::getModel('ddg_automation/campaign')
								    ->setEmail( $email )
								    ->setCustomerId( $quote->getCustomerId() )
								    ->setEventName( 'Lost Basket' )
							        ->setQuoteId($quoteId)
								    ->setMessage('Abandoned Cart :' . $num)
								    ->setCampaignId( $campaignId )
								    ->setStoreId( $storeId )
								    ->setWebsiteId($websiteId)
								    ->setIsSent( null )->save();
						    }
					    }
				    }

			    }
		    }
		    if ($mode == 'all' || $mode == 'guests') {
			    /**
			     * Guests campaigns
			     */
			    foreach ( $this->lostBasketGuests as $num ) {
				    if ( $this->_getLostBasketGuestEnabled( $num, $storeId ) ) {
					    if ( $num == 1 ) {
						    $from = Zend_Date::now( $locale )->subMinute( $this->_getLostBasketGuestIterval( $num, $storeId ) );
					    } else {
						    $from = Zend_Date::now( $locale )->subHour( $this->_getLostBasketGuestIterval( $num, $storeId ) );
					    }
                        $to = clone($from);
                        $from->sub('5', Zend_Date::MINUTE);
					    $quoteCollection = $this->_getStoreQuotes( $from->toString( 'YYYY-MM-dd HH:mm' ), $to->toString( 'YYYY-MM-dd HH:mm' ), $guest = true, $storeId );

					    if ( $quoteCollection->getSize() ) {
						    Mage::helper( 'ddg' )->log( 'Guest lost baskets : ' . $num . ', from : ' . $from->toString( 'YYYY-MM-dd HH:mm' ) . ':' . $to->toString( 'YYYY-MM-dd HH:mm' ) );
					    }
					    $guestCampaignId = $this->_getLostBasketGuestCampaignId( $num, $storeId );
					    foreach ( $quoteCollection as $quote ) {

						    $email        = $quote->getCustomerEmail();
						    $websiteId    = $store->getWebsiteId();
						    $quoteId      = $quote->getId();
						    // upate last quote id for the contact
						    Mage::helper('ddg')->updateLastQuoteId($quoteId, $email, $websiteId);

                            // update abandoned product name for contact
                            $items = $quote->getAllItems();
                            $mostExpensiveItem = false;
                            foreach ($items as $item) {
                                /** @var $item Mage_Sales_Model_Quote_Item */
                                if ($mostExpensiveItem == false)
                                    $mostExpensiveItem = $item;
                                elseif ($item->getPrice() > $mostExpensiveItem->getPrice())
                                    $mostExpensiveItem = $item;
                            }
                            if ($mostExpensiveItem)
                                Mage::helper('ddg')->updateAbandonedProductName($mostExpensiveItem->getName(), $email, $websiteId);

						    //send email only if the interval limit passed, no emails during this interval
						    $campignFound = $this->_checkCustomerCartLimit( $email, $storeId );

						    //no campign found for interval pass
                            if (!$campignFound) {
							    //save lost basket for sending
							    $sendModel = Mage::getModel('ddg_automation/campaign')
								    ->setEmail( $email )
								    ->setEventName( 'Lost Basket' )
								    ->setQuoteId($quoteId)
								    ->setCheckoutMethod( 'Guest' )
								    ->setMessage('Guest Abandoned Cart : ' . $num)
								    ->setCampaignId( $guestCampaignId )
								    ->setStoreId( $storeId )
								    ->setWebsiteId($websiteId)
								    ->setIsSent( null )->save();
						    }
					    }
				    }
			    }
		    }
        }
    }

    private function _getLostBasketCustomerCampaignId($num, $storeId)
    {
        $store = Mage::app()->getStore($storeId);
        return $store->getConfig(constant('self::XML_PATH_LOSTBASKET_CUSTOMER_CAMPAIGN_' . $num));
    }
    private function _getLostBasketGuestCampaignId($num, $storeId)
    {
        $store = Mage::app()->getStore($storeId);
        return $store->getConfig(constant('self::XML_PATH_LOSTBASKET_GUEST_CAMPAIGN_'. $num));
    }

    private function _getLostBasketCustomerInterval($num, $storeId)
    {
        $store = Mage::app()->getstore($storeId);
        return $store->getConfig(constant('self::XML_PATH_LOSTBASKET_CUSTOMER_INTERVAL_' . $num));
    }

    private function _getLostBasketGuestIterval($num, $storeId)
    {
        $store = Mage::app()->getStore($storeId);
        return $store->getConfig(constant('self::XML_PATH_LOSTBASKET_GUEST_INTERVAL_' . $num));
    }

    protected function _getLostBasketCustomerEnabled($num, $storeId)
    {
        $store = Mage::app()->getStore($storeId);
        $enabled = $store->getConfig(constant('self::XML_PATH_LOSTBASKET_CUSTOMER_ENABLED_' . $num));
        return $enabled;

    }

    protected function _getLostBasketGuestEnabled($num, $storeId)
    {
        $store = Mage::app()->getStore($storeId);
        return $store->getConfig(constant('self::XML_PATH_LOSTBASKET_GUEST_ENABLED_' . $num));
    }

    /**
     * @param string $from
     * @param string $to
     * @param bool $guest
     * @param int $storeId
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    private function _getStoreQuotes($from = null, $to = null, $guest = false, $storeId = 0)
    {
	    $updated = array(
            'from' => $from,
            'to' => $to,
            'date' => true);

        $salesCollection = Mage::getResourceModel('sales/quote_collection')
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('items_count', array('gt' => 0))
            ->addFieldToFilter('customer_email', array('neq' => ''))
            ->addFieldToFilter('store_id', $storeId)
            ->addFieldToFilter('updated_at', $updated);
        //guests
	    if ($guest) {
	        $salesCollection->addFieldToFilter( 'main_table.customer_id', array( 'null' => true ) );
        } else {
		    //customers
	        $salesCollection->addFieldToFilter( 'main_table.customer_id', array( 'notnull' => true ) );
        }

        //process rules on collection
        $ruleModel = Mage::getModel('ddg_automation/rules');
        $salesCollection = $ruleModel->process(
            $salesCollection, Dotdigitalgroup_Email_Model_Rules::ABANDONED, Mage::app()->getStore($storeId)->getWebsiteId()
        );

	    return $salesCollection;
    }

	/**
	 * Check customer campaign that was sent by a limit from config.
	 * Return false for any found for this period.
	 *
	 * @param $email
	 * @param $storeId
	 *
	 * @return bool
	 */
	private function _checkCustomerCartLimit($email, $storeId) {

		$cartLimit = Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_ABANDONED_CART_LIMIT, $storeId);
		$locale = Mage::app()->getLocale()->getLocale();

		//no limit is set skip
		if (! $cartLimit)
			return false;

		//time diff
		$to = Zend_Date::now($locale);
		$from = Zend_Date::now($locale)->subHour($cartLimit);

		$updated = array(
			'from' => $from,
			'to' => $to,
			'date' => true
		);

		//number of campigns during this time
		$campaignLimit = Mage::getModel('ddg_automation/campaign')->getCollection()
			->addFieldToFilter('email', $email)
			->addFieldToFilter('event_name', 'Lost Basket')
			->addFieldToFilter('sent_at', $updated)
			->count()
		;

		if ($campaignLimit)
			return true;

		return false;
	}
}