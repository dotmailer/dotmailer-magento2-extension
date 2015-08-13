<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

class Customer
{

	public $customer;
	public $customerData;
	public $reviewCollection;

	//enterprise reward
	public $reward;

	public $rewardCustomer;
	public $rewardLastSpent = "";
	public $rewardLastEarned = "";
	public $rewardExpiry = "";

	protected $_mapping_hash;
	protected $_helper;

	private $subscriber_status = array(
		\Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED => 'Subscribed',
		\Magento\Newsletter\Model\Subscriber::STATUS_NOT_ACTIVE => 'Not Active',
		\Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED => 'Unsubscribed',
		\Magento\Newsletter\Model\Subscriber::STATUS_UNCONFIRMED => 'Unconfirmed'
	);

	private $attribute_check = false;

	/**
	 * constructor, mapping hash to map.
	 *
	 */
	public function __construct(
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Magento\Store\Model\StoreManagerInterface $store,
		\Magento\Review\Model\Resource\Review\Collection $reviewCollection,
		\Dotdigitalgroup\Email\Helper\Data $helper
	)
	{
		$this->_helper = $helper;
		$this->_store = $store;
		$this->_objectManager = $objectManager;
		$this->reviewCollection = $reviewCollection;
	}

	/**
	 * Set key value data.
	 *
	 * @param $data
	 */
	public function setData($data)
	{
		$this->customerData[] = $data;
	}

	/**
	 * Set customer data.
	 *
	 */
	public function setCustomerData($customer)
	{
		$this->customer = $customer;
		$this->setReviewCollection();

		$website = $customer->getStore()->getWebsite();

		if ($website && $this->_helper->isSweetToothToGo($website))
			$this->setRewardCustomer($customer);

		foreach ($this->getMappingHash() as $key => $field) {

			/**
			 * call user function based on the attribute mapped.
			 */
			$function = 'get';
			$exploded = explode('_', $key);
			foreach ($exploded as $one) {
				$function .= ucfirst($one);
			}
			try{
				$value = call_user_func(array('self', $function));
				$this->customerData[$key] = $value;
			}catch (\Exception $e){

			}
		}
	}

	/**
	 * Customer reviews.
	 */
	public function setReviewCollection()
	{
		$customer_id = $this->customer->getId();
		$collection = $this->reviewCollection->addCustomerFilter($customer_id)
			->setOrder('review_id','DESC');

		$this->reviewCollection = $collection;
	}

	public function getReviewCount()
	{
		return count($this->reviewCollection);
	}

	public function getLastReviewDate(){
		if(count($this->reviewCollection))
			return $this->reviewCollection->getFirstItem()->getCreatedAt();
		return '';
	}

	/**
	 * Set reward customer
	 *
	 */
	public function setRewardCustomer($customer)
	{
		//get tbt reward customer
		$tbt_reward  = $this->_objectManager->create('TBT/Rewards/Model/Customer')->getRewardsCustomer($customer);
		$this->rewardCustomer = $tbt_reward;

		//get transfers collection from tbt reward. only active and order by last updated.
		$lastTransfers = $tbt_reward->getTransfers()
	        ->selectOnlyActive()
	        ->addOrder('last_update_ts', \Magento\Framework\Data\Collection::SORT_ORDER_DESC);

		$spent = $earn = null;

		foreach($lastTransfers as $transfer) {
			// if transfer quantity is greater then 0 then this is last points earned date. keep checking until earn is not null
			if(is_null($earn) && $transfer->getQuantity() > 0){
				$earn  = $transfer->getEffectiveStart();
			}
			// id transfer quantity is less then 0 then this is last points spent date. keep checking until spent is not null
			else if(is_null($spent) && $transfer->getQuantity() < 0) {
				$spent = $transfer->getEffectiveStart();
			}
			// break if both spent and earn are not null (a value has been assigned)
			if(!is_null($spent) && !is_null($earn)) {
				break;
			}
		}

		// if earn is not null (has a value) then assign the value to property
		if($earn)
			$this->rewardLastEarned = $earn;
		// if spent is not null (has a value) then assign the value to property
		if($spent)
			$this->rewardLastSpent = $spent;

		$tbt_expiry = $this->_objectManager->create('TBT\Rewards\Model\Expire')
		                  ->getExpiryDate($tbt_reward);

		// if there is an expiry (has a value) then assign the value to property
		if($tbt_expiry)
			$this->rewardExpiry = $tbt_expiry;
	}

	/**
	 * get customer id.
	 *
	 * @return mixed
	 */
	public function getCustomerId()
	{
		return $this->customer->getId();
	}

	/**
	 * get first name.
	 *
	 * @return mixed
	 */
	public function getFirstname(){
		return $this->customer->getFirstname();
	}

	/**
	 * get last name.
	 *
	 * @return mixed
	 */
	public function getLastname()
	{
		return $this->customer->getLastname();
	}

	/**
	 * get date of birth.
	 *
	 * @return mixed
	 */
	public function getDob()
	{
		return $this->customer->getDob();
	}

	/**
	 * get customer gender.
	 *
	 * @return bool|string
	 */
	public function getGender()
	{
		return $this->_getCustomerGender();
	}

	/**
	 * get customer prefix.
	 *
	 * @return mixed
	 */
	public function getPrefix()
	{
		return $this->customer->getPrefix();
	}

	/**
	 * get customer suffix.
	 *
	 * @return mixed
	 */
	public function getSuffix()
	{
		return $this->customer->getSuffix();
	}

	/**
	 * get website name.
	 *
	 * @return string
	 */
	public function getWebsiteName()
	{
		return $this->_getWebsiteName();
	}

	/**
	 * get store name.
	 *
	 * @return null|string
	 */
	public function getStoreName()
	{
		return $this->_getStoreName();
	}

	/**
	 * get customer created at date.
	 *
	 * @return mixed
	 */
	public function getCreatedAt()
	{
		return $this->customer->getCreatedAt();
	}

	/**
	 * get customer last logged in date.
	 *
	 * @return mixed
	 */
	public function getLastLoggedDate()
	{
		return $this->customer->getLastLoggedDate();
	}

	/**
	 * get cutomer group.
	 *
	 * @return string
	 */
	public function getCustomerGroup()
	{
		return $this->_getCustomerGroup();
	}

	/**
	 * get billing address line 1.
	 *
	 * @return string
	 */
	public function getBillingAddress1()
	{
		return $this->_getStreet($this->customer->getBillingStreet(), 1);
	}

	/**
	 * get billing address line 2.
	 *
	 * @return string
	 */
	public function getBillingAddress2()
	{
		return $this->_getStreet($this->customer->getBillingStreet(), 2);
	}

	/**
	 * get billing city.
	 *
	 * @return mixed
	 */
	public function getBillingCity()
	{
		return $this->customer->getBillingCity();
	}

	/**
	 * get billing country.
	 *
	 * @return mixed
	 */
	public function getBillingCountry()
	{
		return $this->customer->getBillingCountryCode();
	}

	/**
	 * get billing state.
	 *
	 * @return mixed
	 */
	public function getBillingState()
	{
		return $this->customer->getBillingRegion();
	}

	/**
	 * get billing postcode.
	 *
	 * @return mixed
	 */
	public function getBillingPostcode()
	{
		return $this->customer->getBillingPostcode();
	}

	/**
	 * get billing phone.
	 *
	 * @return mixed
	 */
	public function getBillingTelephone()
	{
		return $this->customer->getBillingTelephone();
	}

	/**
	 * get delivery address line 1.
	 *
	 * @return string
	 */
	public function getDeliveryAddress1()
	{
		return $this->_getStreet($this->customer->getShippingStreet(), 1);
	}

	/**
	 * get delivery addrss line 2.
	 *
	 * @return string
	 */
	public function getDeliveryAddress2()
	{
		return $this->_getStreet($this->customer->getShippingStreet(), 2);
	}

	/**
	 * get delivery city.
	 *
	 * @return mixed
	 */
	public function getDeliveryCity()
	{
		return $this->customer->getShippingCity();
	}

	/**
	 * get delivery country.
	 *
	 * @return mixed
	 */
	public function getDeliveryCountry(){
		return $this->customer->getShippingCountryCode();
	}

	/**
	 * get delivery state.
	 *
	 * @return mixed
	 */
	public function getDeliveryState()
	{
		return $this->customer->getShippingRegion();
	}

	/**
	 * get delivery postcode.
	 *
	 * @return mixed
	 */
	public function getDeliveryPostcode()
	{
		return $this->customer->getShippingPostcode();
	}

	/**
	 * get delivery phone.
	 *
	 * @return mixed
	 */
	public function getDeliveryTelephone(){
		return $this->customer->getShippingTelephone();
	}

	/**
	 * get numbser of orders.
	 *
	 * @return mixed
	 */
	public function getNumberOfOrders()
	{
		return $this->customer->getNumberOfOrders();
	}

	/**
	 * get average order value.
	 *
	 * @return mixed
	 */
	public function getAverageOrderValue()
	{
		return $this->customer->getAverageOrderValue();
	}

	/**
	 * get total spend.
	 *
	 * @return mixed
	 */
	public function getTotalSpend()
	{
		return $this->customer->getTotalSpend();
	}

	/**
	 * get last order date.
	 *
	 * @return mixed
	 */
	public function getLastOrderDate()
	{
		return $this->customer->getLastOrderDate();
	}

	/**
	 * get last order id.
	 *
	 * @return mixed
	 */
	public function getLastOrderId()
	{
		return $this->customer->getLastOrderId();
	}

	/**
	 * get last quote id.
	 *
	 * @return mixed
	 */
	public function getLastQuoteId()
	{
		return $this->customer->getLastQuoteId();
	}

	/**
	 * get cutomer id.
	 *
	 * @return mixed
	 */
	public function getId()
	{
		return $this->customer->getId();
	}

	/**
	 * get customer title.
	 *
	 * @return mixed
	 */
	public function getTitle()
	{
		return $this->customer->getPrefix();
	}

	/**
	 * get total refund value.
	 *
	 * @return float|int
	 */
	public function getTotalRefund()
	{
		$orders = Mage::getResourceModel('sales/order_collection')
		              ->addAttributeToFilter('customer_id', $this->customer->getId())
		;
		$totalRefunded = 0;
		foreach ($orders as $order) {
			$refunded = $order->getTotalRefunded();
			$totalRefunded += $refunded;
		}

		return $totalRefunded;
	}

	/**
	 * export to CSV.
	 *
	 * @return mixed
	 */
	public function toCSVArray()
	{
		$result = $this->customerData;
		return $result;
	}

	/**
	 * customer gender.
	 *
	 * @return bool|string
	 * @throws Mage_Core_Exception
	 */
	private function _getCustomerGender()
	{
		$genderId = $this->customer->getGender();
		if (is_numeric($genderId)) {
			$gender = Mage::getResourceModel('customer/customer')
			              ->getAttribute('gender')
			              ->getSource()
			              ->getOptionText($genderId)
			;
			return $gender;
		}

		return '';
	}

	private function _getStreet($street, $line){
		$street = explode("\n", $street);
		if(isset($street[$line - 1]))
			return $street[$line - 1];
		return '';
	}

	private function _getWebsiteName(){
		$websiteId = $this->customer->getWebsiteId();
		$website = $this->_store->getWebsite($websiteId);
		if($website)
			return $website->getName();

		return '';
	}

	private  function _getStoreName()
	{
		$storeId = $this->customer->getStoreId();
		$store = Mage::app()->getStore($storeId);
		if($store)
			return $store->getName();

		return '';
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

	private function _getCustomerGroup(){
		$groupId = $this->customer->getGroupId();
		$group = Mage::getModel('customer/group')->load($groupId);
		if($group){
			return $group->getCode();
		}
		return '';
	}

	/**
	 * mapping hash value.
	 *
	 * @param $value
	 *
	 * @return $this
	 */
	public function setMappigHash($value)
	{
		$this->_mapping_hash = $value;
		return $this;
	}

	public function getRewardReferralUrl()
	{
		if(Mage::helper('ddg')->isSweetToothToGo($this->customer->getStore()->getWebsite()))
			return (string) Mage::helper('rewardsref/url')->getUrl($this->customer);

		return '';
	}

	public function getRewardPointBalance()
	{
		return $this->cleanString($this->rewardCustomer->getPointsSummary());
	}

	public function getRewardPointPending()
	{
		return $this->cleanString($this->rewardCustomer->getPendingPointsSummary());
	}

	public function getRewardPointPendingTime()
	{
		return $this->cleanString($this->rewardCustomer->getPendingTimePointsSummary());
	}

	public function getRewardPointOnHold()
	{
		return $this->cleanString($this->rewardCustomer->getOnHoldPointsSummary());
	}

	public function getRewardPointExpiration()
	{
		if($this->rewardExpiry != "")
			return Mage::getModel('core/date')->date('Y/m/d', strtotime($this->rewardExpiry));
		return $this->rewardExpiry;
	}

	public function getRewardPointLastSpent()
	{
		return $this->rewardLastSpent;
	}

	public function getRewardPointLastEarn()
	{
		return $this->rewardLastEarned;
	}

	public function cleanString($string)
	{
		$cleanedString = preg_replace("/[^0-9]/","",$string);
		if($cleanedString != "")
			return (int) number_format($cleanedString, 0, '.', '');
		return 0;
	}

	public function getSubscriberStatus()
	{
		$subscriber = Mage::getModel('newsletter/subscriber')->loadByCustomer($this->customer);
		if($subscriber->getCustomerId())
			return $this->subscriber_status[$subscriber->getSubscriberStatus()];
	}

	/**
	 * Reward points balance.
	 * @return int
	 */
	public function getRewardPoints() {
		if (!$this->reward)
			$this->_setReward();
		$rewardPoints = $this->reward->getPointsBalance();

		return $rewardPoints;
	}

	/**
	 * Currency amount points.
	 * @return mixed
	 */
	public function getRewardAmount() {
		if (!$this->reward)
			$this->_setReward();

		return $this->reward->getCurrencyAmount();
	}

	/**
	 * Expiration date to use the points.
	 * @return string
	 */
	public function getExpirationDate()
	{
		//set reward for later use
		if (!$this->reward)
			$this->_setReward();


		$expiredAt = $this->reward->getExpirationDate();

		if ($expiredAt) {
			$date = Mage::helper('core')->formatDate($expiredAt, 'short', true);
		} else {
			$date = '';
		}

		return $date;
	}


	private function _setReward() {
		$collection = Mage::getModel('enterprise_reward/reward_history')->getCollection()
		                  ->addCustomerFilter($this->customer->getId())
		                  ->addWebsiteFilter($this->customer->getWebsiteId())
		                  ->setExpiryConfig(Mage::helper('enterprise_reward')->getExpiryConfig())
		                  ->addExpirationDate($this->customer->getWebsiteId())
		                  ->skipExpiredDuplicates()
		                  ->setDefaultOrder()
		                  ->getFirstItem()
		;

		$this->reward = $collection;
	}


	/**
	 * Customer segments id.
	 * @return string
	 */
	public function getCustomerSegments()
	{
		$contactModel = Mage::getModel('ddg_automation/contact')->getCollection()
		                    ->addFieldToFilter('customer_id', $this->getCustomerId())
		                    ->addFieldToFilter('website_id', $this->customer->getWebsiteId())
		                    ->getFirstItem();
		if ($contactModel)
			return $contactModel->getSegmentIds();

		return '';
	}



	/**
	 * Last used reward points.
	 * @return mixed
	 */
	public function getLastUsedDate()
	{
		//last used from the reward history based on the points delta used
		$lastUsed = Mage::getModel('enterprise_reward/reward_history')->getCollection()
		                ->addCustomerFilter($this->customer->getId())
		                ->addWebsiteFilter($this->customer->getWebsiteId())
		                ->addFieldToFilter('points_delta', array('lt'=> 0))
		                ->setDefaultOrder()
		                ->getFirstItem()
		                ->getCreatedAt()
		;

		//for any valid date
		if ($lastUsed)
			return $date = Mage::helper('core')->formatDate($lastUsed, 'short', true);

		return '';
	}



	/**
	 * get most purchased category
	 *
	 * @return string
	 */
	public function getMostPurCategory()
	{
		$id = $this->customer->getMostCategoryId();
		if($id){
			return Mage::getModel('catalog/category')
			           ->load($id)
			           ->setStoreId($this->customer->getStoreId())
			           ->getName();
		}
		return "";
	}

	/**
	 * get most purchased brand
	 *
	 * @return string
	 */
	public function getMostPurBrand()
	{
		$brand = $this->customer->getMostBrand();
		if($brand)
			return $brand;
		return "";
	}

	/**
	 * get most frequent day of purchase
	 *
	 * @return string
	 */
	public function getMostFreqPurDay()
	{
		$day = $this->customer->getWeekDay();
		if($day)
			return $day;
		return "";
	}

	/**
	 * get most frequent month of purchase
	 *
	 * @return string
	 */
	public function getMostFreqPurMon()
	{
		$month = $this->customer->getMonthDay();
		if($month)
			return $month;
		return "";
	}

	/**
	 * get first purchased category
	 *
	 * @return string
	 */
	public function getFirstCategoryPur()
	{
		$id = $this->customer->getFirstCategoryId();
		if($id){
			return Mage::getModel('catalog/category')
			           ->load($id)
			           ->setStoreId($this->customer->getStoreId())
			           ->getName();
		}
		return "";
	}

	/**
	 * get last purchased category
	 *
	 * @return string
	 */
	public function getLastCategoryPur()
	{
		$id = $this->customer->getLastCategoryId();
		if($id){
			return Mage::getModel('catalog/category')
			           ->setStoreId($this->customer->getStoreId())
			           ->load($id)
			           ->getName();
		}
		return "";
	}

	/**
	 * get first purchased brand
	 *
	 * @return string
	 */
	public function getFirstBrandPur()
	{
		if(!$this->attribute_check){
			$attribute = Mage::getModel('catalog/resource_eav_attribute')
			                 ->loadByCode('catalog_product', 'manufacturer');

			if($attribute->getId())
				$this->attribute_check = true;
		}

		if($this->attribute_check){
			$id = $this->customer->getProductIdForFirstBrand();
			if($id){
				$brand = Mage::getModel('catalog/product')
				             ->setStoreId($this->customer->getStoreId())
				             ->load($id)
				             ->getAttributeText('manufacturer');
				if($brand)
					return $brand;
			}
		}
		return "";
	}

	/**
	 * get last purchased brand
	 *
	 * @return string
	 */
	public function getLastBrandPur()
	{
		if(!$this->attribute_check){
			$attribute = Mage::getModel('catalog/resource_eav_attribute')
			                 ->loadByCode('catalog_product', 'manufacturer');

			if($attribute->getId())
				$this->attribute_check = true;
		}

		if($this->attribute_check){
			$id = $this->customer->getProductIdForLastBrand();
			if($id){
				$brand = Mage::getModel('catalog/product')
				             ->setStoreId($this->customer->getStoreId())
				             ->load($id)
				             ->getAttributeText('manufacturer');
				if($brand)
					return $brand;
			}
			return "";
		}
	}

	/**
	 * get last increment id
	 *
	 * @return mixed
	 */
	public function getLastIncrementId()
	{
		return $this->customer->getLastIncrementId();
	}
}