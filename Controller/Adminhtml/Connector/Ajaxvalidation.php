<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Connector;


class Ajaxvalidation extends \Magento\Backend\App\Action
{
	protected $_objectManager;
	protected $_helper;

	public function __construct(
		\Dotdigitalgroup\Email\Helper\Data $data,
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Magento\Backend\App\Action\Context $context)
	{
		$this->_helper = $data;
		$this->_objectManager = $objectManager;
		parent::__construct($context);

	}
	/**
	 * Validate api user.
	 */
	public function execute()
	{
		$params = $this->getRequest()->getParams();
		$apiUsername     = $params['api_username'];
		$apiPassword     = base64_decode($params['api_password']);

		//validate api, check against account info.

		$client = $this->_helper->getWebsiteApiClient();
		$result = $client->validate($apiUsername, $apiPassword);

		$resonseData['success'] = true;
		//validation failed
		if (! $result) {
			$resonseData['success'] = false;
			$responseData['message'] = 'Authorization has been denied for this request.';
		}

		$this->getResponse()->representJson(
			$this->_objectManager->create('Magento\Framework\Json\Helper\Data')->jsonEncode($resonseData)
		);

	}

}
