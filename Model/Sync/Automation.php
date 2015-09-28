<?php

namespace Dotdigitalgroup\Email\Model\Sync;

class Automation
{

	const AUTOMATION_TYPE_NEW_CUSTOMER      = 'customer_automation';
	const AUTOMATION_TYPE_NEW_SUBSCRIBER    = 'subscriber_automation';
	const AUTOMATION_TYPE_NEW_ORDER         = 'order_automation';
	const AUTOMATION_TYPE_NEW_GUEST_ORDER   = 'guest_order_automation';
	const AUTOMATION_TYPE_NEW_REVIEW        = 'review_automation';
	const AUTOMATION_TYPE_NEW_WISHLIST      = 'wishlist_automation';
	const AUTOMATION_STATUS_PENDING         = 'pending';
	//automation enrolment limit
	public $limit = 100;
	public $email;
	public $typeId;
	public $websiteId;
	public $storeName;
	public $programId;
	public $programStatus = 'Active';
	public $programMessage;
	public $automationType;


	protected $_helper;
	protected $_storeManager;
	protected $_objectManager;
	protected $_resource;
	protected $_localeDate;
	protected $_scopeConfig;
	protected $_automationFactory;

	public function __construct(
		\Dotdigitalgroup\Email\Model\Resource\Automation\CollectionFactory $automationFactory,
		\Magento\Framework\App\Resource $resource,
		\Dotdigitalgroup\Email\Helper\Data $helper,
		\Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
		\Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\ObjectManagerInterface $objectManager
	)
	{
		$this->_automationFactory = $automationFactory;
		$this->_helper = $helper;
		$this->_storeManager = $storeManagerInterface;
		$this->_resource = $resource;
		$this->_localeDate = $localeDate;
		$this->_scopeConfig = $scopeConfig;
		$this->_objectManager = $objectManager;
	}

	public function sync()
	{
		//automation statuses to filter
		$automationCollection = $this->_automationFactory->create()
             ->addFieldToSelect( 'automation_type' )
             ->addFieldToFilter( 'enrolment_status', self::AUTOMATION_STATUS_PENDING );
		$automationCollection->getSelect()->group( 'automation_type' );
		//active types
		$automationTypes = $automationCollection->getColumnValues( 'automation_type' );

		//send the campaign by each types
		foreach ( $automationTypes as $type ) {

			$contacts = array();
			//reset the collection
			$automationCollection = $this->_automationFactory->create()
                 ->addFieldToFilter( 'enrolment_status', self::AUTOMATION_STATUS_PENDING )
                 ->addFieldToFilter( 'automation_type', $type );
			//limit because of the each contact request to get the id
			$automationCollection->getSelect()->limit( $this->limit );
			foreach ( $automationCollection as $automation ) {
				$type = $automation->getAutomationType();
				//customerid, subscriberid, wishlistid..
				$email           = $automation->getEmail();
				$this->typeId    = $automation->getTypeId();
				$this->websiteId = $automation->getWebsiteId();
				$this->programId = $automation->getProgramId();
				$this->storeName = $automation->getStoreName();
				$contactId = $this->_helper->getContactId( $email, $this->websiteId );
				//contact id is valid, can update datafields
				if ( $contactId ) {
					//need to update datafields
					$this->updateDatafieldsByType( $this->automationType, $email );
					$contacts[ $automation->getId() ] = $contactId;
				} else {
					// the contact is suppressed or the request failed
					$automation->setEnrolmentStatus('Suppressed')
						->save();
				}
			}
			//only for subscribed contacts
			if ( ! empty( $contacts ) && $type != '' && $this->_checkCampignEnrolmentActive( $this->programId ) ) {
				$result = $this->sendContactsToAutomation( array_values( $contacts ) );
				//check for error message
				if ( isset( $result->message ) ) {
					$this->programStatus  = 'Failed';
					$this->programMessage = $result->message;
				}
				//program is not active
			} elseif ( $this->programMessage == 'Error: ERROR_PROGRAM_NOT_ACTIVE ' ) {
				$this->programStatus = 'Deactivated';
			}
			//update contacts with the new status, and log the error message if failes
			$coreResource = $this->_resource;
			$conn = $coreResource->getConnection( 'core_write' );
			try {
				$contactIds = array_keys($contacts);
				$bind = array(
					'enrolment_status' => $this->programStatus,
					'message'          => $this->programMessage,
					'updated_at'       => $this->_localeDate->date(null, null, false)->format('Y-m-d H:i:s')

				);
				$where = array('id IN(?)' => $contactIds);
				$num = $conn->update( $coreResource->getTableName( 'email_automation' ),
					$bind,
					$where
				);
				//number of updated records
				if ($num)
					$this->_helper->log('Automation type : ' . $type . ', updated : ' . $num);
			} catch ( \Exception $e ) {
				throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
			}
		}
	}
	/**
	 * update single contact datafields for this automation type.
	 *
	 * @param $type
	 */
	public function updateDatafieldsByType($type, $email )
	{
		switch($type) {
			case self::AUTOMATION_TYPE_NEW_CUSTOMER :
				$this->_updateDefaultDatafields($email);
				break;
			case self::AUTOMATION_TYPE_NEW_SUBSCRIBER :
				$this->_updateDefaultDatafields($email);
				break;
			case self::AUTOMATION_TYPE_NEW_ORDER :
				$this->_updateNewOrderDatafields($email);
				break;
			case self::AUTOMATION_TYPE_NEW_GUEST_ORDER:
				$this->_updateNewOrderDatafields($email);
				break;
			case self::AUTOMATION_TYPE_NEW_REVIEW :
				$this->_updateNewOrderDatafields($email);
				break;
			case self::AUTOMATION_TYPE_NEW_WISHLIST:
				$this->_updateDefaultDatafields($email);
				break;
			default:
				$this->_updateDefaultDatafields($email);
				break;
		}
	}
	private function _updateDefaultDatafields($email)
	{

		$website = $this->_storeManager->getWebsite($this->websiteId);
		$this->_helper->updateDataFields($email, $website, $this->storeName);
	}
	private function _updateNewOrderDatafields($email)
	{
		$website = $this->_storeManager->getWebsite($this->websiteId);
		$orderModel = $this->_objectManager->create('Magento\Sales\Model\Order')
			->load($this->typeId);
		//data fields
		if($last_order_id = $website->getConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_ID)){
			$data[] = array(
				'Key' => $last_order_id,
				'Value' => $orderModel->getId()
			);
		}
		if($order_increment_id = $website->getConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_INCREMENT_ID)){
			$data[] = array(
				'Key' => $order_increment_id,
				'Value' => $orderModel->getIncrementId()
			);
		}
		if($store_name = $website->getConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_STORE_NAME)){
			$data[] = array(
				'Key' => $store_name,
				'Value' => $this->storeName
			);
		}
		if($website_name = $website->getConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_WEBSITE_NAME)){
			$data[] = array(
				'Key' => $website_name,
				'Value' => $website->getName()
			);
		}
		if($last_order_date = $website->getConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_DATE)){
			$data[] = array(
				'Key' => $last_order_date,
				'Value' => $orderModel->getCreatedAt()
			);
		}
		if(($customer_id = $website->getConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_ID)) && $orderModel->getCustomerId()){
			$data[] = array(
				'Key' => $customer_id,
				'Value' => $orderModel->getCustomerId()
			);
		}
		if(! empty($data)){
			//update data fields
			$client = $this->_helper->getWebsiteApiClient($website);
			$client->updateContactDatafieldsByEmail($orderModel->getCustomerEmail(), $data);
		}
	}
	/**
	 * Program check if is valid and active.
	 * @param $programId
	 *
	 * @return bool
	 */
	private function _checkCampignEnrolmentActive($programId)
	{
		//program is not set
		if (!$programId)
			return false;
		$client = $this->_helper->getWebsiteApiClient($this->websiteId);
		$program = $client->getProgramById($programId);
		//program status
		if (isset($program->status))
			$this->programStatus = $program->status;
		if (isset($program->status) && $program->status == 'Active') {
			return true;
		}
		return false;
	}
	/**
	 * Enrol contacts for a program.
	 * @param $contacts
	 *
	 * @return null
	 */
	public function sendContactsToAutomation($contacts)
	{
		$client = $this->_helper->getWebsiteApiClient($this->websiteId);
		$data = array(
			'Contacts'     => $contacts,
			'ProgramId'    => $this->programId,
			'AddressBooks' => array()
		);
		//api add contact to automation enrolment
		$result = $client->postProgramsEnrolments( $data );
		return $result;
	}

}