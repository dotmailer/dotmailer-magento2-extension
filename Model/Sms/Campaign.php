<?php

namespace Dotdigitalgroup\Email\Model\Sms;

class Campaign
{
    const UK_TELEPHONE_PATTERN = '/^(\+44\s?7\d{3}|\(?07\d{3}\)?)\s?\d{3}\s?\d{3}$/';

	/**
	 * @var int
	 */
	private $_storeId;
	/**
	 * @var string
	 */
	private $_status;
	/**
	 * @var string
	 */
	private $_customerFirstName;
	/**
	 * @var string
	 */
	private $_customerTelephone;

	/**
	 * @var int
	 */
	private $_incrementId;

	/**
	 * @var array
	 */
	private $_allsms = array(1, 2, 3, 4);
	/**
	 * filter for the variables
	 * @var array
	 */
	private $_vars = array('/customer_name/', '/order_number/', '/{{var /', '/}}/');

	protected $_objectManager;
	protected $_storeManager;
	protected $_helper;
	protected $_scopeConfig;

	public function __construct(
		\Dotdigitalgroup\Email\Helper\Data $data,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
		\Magento\Framework\App\ObjectManager $objectManager
	)
	{
		$this->_helper = $data;
		$this->_scopeConfig = $scopeConfig;
		$this->_objectManager = $objectManager;
		$this->_storeManager = $storeManagerInterface;
	}
	/**
	 * constructor.
	 *
	 * @param $order
	 */
	public function setOrder($order)
    {
        $this->_storeId             = $order->getStoreId();
	    $billingAddress             = $order->getBillingAddress();
	    $this->_customerFirstName   = $order->getCustomerFirstname();
	    $this->_incrementId         = $order->getIncrementId();
		$this->_customerTelephone   = $billingAddress->getTelephone();
    }
    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->_status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->_status = $status;
    }

    public function sendSms()
    {
        $website = $this->_storeManager->getStore($this->_storeId)->getWebsite();
        $client = $this->_helper->getWebsiteApiClient($website);
        //all available sms in config
        foreach ($this->_allsms as $num) {

            $enabled = (bool)$this->_scopeConfig->getValue(constant('\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SMS_ENABLED_' . $num));
            if ($enabled) {
                $status = $this->_scopeConfig->getValue(constant('\Dotdigitalgroup\Email_Helper\Config::XML_PATH_CONNECTOR_SMS_STATUS_' . $num));
                $message = $this->_processMessage($this->_scopeConfig->getValue(constant('\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SMS_MESSAGE_' . $num)));
                $match = preg_match(self::UK_TELEPHONE_PATTERN, $this->_customerTelephone);
                if ($match != false) {
                    $codePhone = preg_replace('/\A(0){1}+/', '+44', $this->_customerTelephone);
                    //status and telephone valid
                    if ($this->_status == $status) {
                        $this->_helper->log('sending sms message with status : ' . $status . ' and ' . $codePhone);
                        $client->postSmsMessagesSendTo($codePhone, $message);
                    }
                } else {
                    $this->_helper->log('SMS: phone not valid for UK : ' . $this->_customerTelephone);
                }
            }
        }
    }

    /**
     * @param $message
     * @return mixed
     */
    protected function _processMessage($message)
    {
        $replacemant = array();
        if (preg_match('/{{var/', $message)) {
            $replacemant[] = $this->_customerFirstName;
            $replacemant[] = $this->_incrementId;
            $replacemant[] = '';
            $replacemant[] = '';
            $message = preg_replace($this->_vars, $replacemant, $message);
        }
        return substr($message, 0, 160);
    }

}