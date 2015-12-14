<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Dotdigitalgroup\Email\Observer\Adminhtml;

use Magento\Framework\Event\ObserverInterface;

class AccountTransactionalDataCheck implements ObserverInterface
{

	protected $_helper;
	protected $_context;
	protected $_request;
	protected $_storeManager;
	protected $messageManager;
	protected $_objectManager;

	public function __construct(
		\Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
		\Dotdigitalgroup\Email\Helper\Data $data,
		\Magento\Backend\App\Action\Context $context,
		\Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface
	)
	{
		$this->_helper = $data;
		$this->_context = $context;
		$this->_contactFactory = $contactFactory;
		$this->_request = $context->getRequest();
		$this->_storeManager = $storeManagerInterface;
		$this->messageManager = $context->getMessageManager();
		$this->_objectManager = $objectManagerInterface;
	}

	/**
	 * @param \Magento\Framework\Event\Observer $observer
	 *
	 * @return $this
	 */
	public function execute(\Magento\Framework\Event\Observer $observer)
	{
		//scope to retrieve the website id
		$scopeId = 0;
		$request = $this->_context->getRequest();
		if ($website = $request->getParam('website')) {
			//use webiste
			$scope = 'websites';
			$scopeId = $this->_storeManager->getWebsite($website)->getId();
		} else {
			//set to default
			$scope = "default";
		}
		//webiste by id
		$website = $this->_storeManager->getWebsite($scopeId);

		//configuration saved for the wishlist and order sync
		$wishlistEnabled = $website->getConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_WISHLIST_ENABLED, $scope, $scopeId);
		$orderEnabled = $website->getConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED);

		//only for modification for order and wishlist
		if ($orderEnabled || $wishlistEnabled) {
			//client by website id
			$client = $this->_helper->getWebsiteApiClient($scopeId);

			//call request for account info
			$response = $client->getAccountInfo();

			//properties must be checked
			if (isset($response->properties)) {
				$accountInfo = $response->properties;
				$result = $this->_checkForOption(\Dotdigitalgroup\Email\Model\Apiconnector\Client::API_ERROR_TRANS_ALLOWANCE, $accountInfo);

				//account is disabled to use transactional data
				if (! $result) {
					$message = 'Transactional Data For This Account Is Disabled. Call Support To Enable.';
					//send admin message
					$this->messageManager->addError($message);
					//disable the config for wishlist and order sync
					$this->_helper->disableTransactionalDataConfig($scope, $scopeId);
				}
			}
		}

		return $this;
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
}
