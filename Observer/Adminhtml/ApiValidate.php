<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Dotdigitalgroup\Email\Observer\Adminhtml;

use Magento\Framework\Event\ObserverInterface;

class ApiValidate implements ObserverInterface
{
	protected $_helper;
	protected $_context;
	protected $_request;
	protected $_storeManager;
	protected $messageManager;
	protected $_objectManager;
	protected $_writer;

	public function __construct(
		\Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
		\Dotdigitalgroup\Email\Helper\Data $data,
		\Magento\Backend\App\Action\Context $context,
		\Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
		\Magento\Framework\App\Config\Storage\Writer $writer
	)
	{
		$this->_helper = $data;
		$this->_context = $context;
		$this->_contactFactory = $contactFactory;
		$this->_request = $context->getRequest();
		$this->_storeManager = $storeManagerInterface;
		$this->messageManager = $context->getMessageManager();
		$this->_objectManager = $context->getObjectManager();
		$this->_writer = $writer;
	}

	/**
	 * @param \Magento\Framework\Event\Observer $observer
	 *
	 * @return $this
	 */
	public function execute(\Magento\Framework\Event\Observer $observer)
	{
		$groups = $this->_context->getRequest()->getPost('groups');

		if (isset($groups['api']['fields']['username']['inherit']) || isset($groups['api']['fields']['password']['inherit']))
			return $this;

		$apiUsername =  isset($groups['api']['fields']['username']['value'])? $groups['api']['fields']['username']['value'] : false;
		$apiPassword =  isset($groups['api']['fields']['password']['value'])? $groups['api']['fields']['password']['value'] : false;

		//skip if the inherit option is selected
		if ($apiUsername && $apiPassword) {
			$this->_helper->log('----VALIDATING ACCOUNT---');
			$testModel = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Apiconnector\Test');
			$isValid = $testModel->validate($apiUsername, $apiPassword);
			if ($isValid) {

				//save endpoint for account
				foreach($isValid->properties as $property){
					if($property->name == 'ApiEndpoint' && strlen($property->value)){
						$this->_saveApiEndpoint($property->value);
						break;
					}
				}

				/**
				 * Send install info
				 */
				//$testModel->sendInstallConfirmation();
				$this->messageManager->addSuccess(__('API Credentials Valid.'));
			} else {

				$this->messageManager->addWarning(__('Authorization has been denied for this request.'));
			}
		}
		return $this;
	}

	protected function _saveApiEndpoint($apiEndpoint)
	{
		$this->_writer->save(
			\Dotdigitalgroup\Email\Helper\Config::PATH_FOR_API_ENDPOINT,
			$apiEndpoint
		);
	}
}
