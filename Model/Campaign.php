<?php

namespace Dotdigitalgroup\Email\Model;

class Campaign extends \Magento\Framework\Model\AbstractModel
{
	//xml path configuration
	const XML_PATH_LOSTBASKET_1_ENABLED      = 'abandoned_carts/customers/enabled_1';
	const XML_PATH_LOSTBASKET_2_ENABLED      = 'abandoned_carts/customers/enabled_2';
	const XML_PATH_LOSTBASKET_3_ENABLED      = 'abandoned_carts/customers/enabled_3';

	const XML_PATH_LOSTBASKET_1_INTERVAL     = 'abandoned_carts/customers/send_after_1';
	const XML_PATH_LOSTBASKET_2_INTERVAL     = 'abandoned_carts/customers/send_after_2';
	const XML_PATH_LOSTBASKET_3_INTERVAL     = 'abandoned_carts/customers/send_after_3';

	const XML_PATH_TRIGGER_1_CAMPAIGN        = 'abandoned_carts/customers/campaign_1';
	const XML_PATH_TRIGGER_2_CAMPAIGN        = 'abandoned_carts/customers/campaign_2';
	const XML_PATH_TRIGGER_3_CAMPAIGN        = 'abandoned_carts/customers/campaign_3';

	const XML_PATH_GUEST_LOSTBASKET_1_ENABLED  = 'abandoned_carts/guests/enabled_1';
	const XML_PATH_GUEST_LOSTBASKET_2_ENABLED  = 'abandoned_carts/guests/enabled_2';
	const XML_PATH_GUEST_LOSTBASKET_3_ENABLED  = 'abandoned_carts/guests/enabled_3';

	const XML_PATH_GUEST_LOSTBASKET_1_INTERVAL = 'abandoned_carts/guests/send_after_1';
	const XML_PATH_GUEST_LOSTBASKET_2_INTERVAL = 'abandoned_carts/guests/send_after_2';
	const XML_PATH_GUEST_LOSTBASKET_3_INTERVAL = 'abandoned_carts/guests/send_after_3';

	const XML_PATH_GUEST_LOSTBASKET_1_CAMPAIGN = 'abandoned_carts/guests/campaign_1';
	const XML_PATH_GUEST_LOSTBASKET_2_CAMPAIGN = 'abandoned_carts/guests/campaign_2';
	const XML_PATH_GUEST_LOSTBASKET_3_CAMPAIGN = 'abandoned_carts/guests/campaign_3';


	//error messages
	const SEND_EMAIL_CONTACT_ID_MISSING = 'Error : missing contact id - will try later to send ';



	protected $_dateTime;

	/**
	 * @param \Magento\Framework\Model\Context $context
	 * @param \Magento\Framework\Registry $registry
	 * @param \Magento\Framework\Stdlib\DateTime $dateTime
	 * @param \Magento\Framework\Model\Resource\AbstractResource $resource
	 * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
	 * @param array $data
	 */
	public function __construct(
		\Magento\Framework\Model\Context $context,
		\Magento\Framework\Registry $registry,
		\Magento\Framework\Stdlib\DateTime $dateTime,
		\Magento\Framework\Model\Resource\AbstractResource $resource = null,
		\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
		array $data = []
	) {
		$this->_dateTime = $dateTime;
		parent::__construct($context, $registry, $resource, $resourceCollection, $data);
	}
	/**
	 * constructor
	 */
	public function _construct()
	{
		parent::_construct();
		$this->_init('Dotdigitalgroup\Email\Model\Resource\Campaign');
	}

	/**
	 * @param $quoteId
	 * @param $storeId
	 * @return mixed
	 */
	public function loadByQuoteId($quoteId, $storeId)
	{
		$collection = $this->getCollection()
           ->addFieldToFilter('quote_id', $quoteId)
           ->addFieldToFilter('store_id', $storeId);

		if ($collection->getSize()) {
			return $collection->getFirstItem();
		} else {
			$this->setQuoteId($quoteId)
			     ->setStoreId($storeId);
		}

		return $this;
	}

	/**
	 * Prepare data to be saved to database
	 *
	 * @return $this
	 */
	public function beforeSave()
	{
		parent::beforeSave();
		if ($this->isObjectNew()) {
			$this->setCreatedAt($this->_dateTime->formatDate(true));
		}
		$this->setUpdatedAt($this->_dateTime->formatDate(true));
		return $this;
	}

}