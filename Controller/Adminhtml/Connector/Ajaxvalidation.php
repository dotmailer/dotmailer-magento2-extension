<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Connector;


class Ajaxvalidation extends \Magento\Backend\App\Action
{
	protected $_objectManager;
	protected $data;

	public function __construct(
		\Dotdigitalgroup\Email\Helper\Data $data,
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Magento\Backend\App\Action\Context $context)
	{
		$this->data = $data;
		$this->_objectManager = $objectManager;
		parent::__construct($context);

	}
	/**
	 * Validate api user.
	 */
	public function executeInternal()
	{
		$params = $this->getRequest()->getParams();
		$apiUsername     = $params['api_username'];
		$apiPassword     = base64_decode($params['api_password']);
		//validate api, check against account info.
		$client = $this->data->getWebsiteApiClient();
		$result = $client->validate($apiUsername, $apiPassword);

		$resonseData['success'] = true;
		//validation failed
		if (! $result) {
			$resonseData['success'] = false;
			$resonseData['message'] = 'Authorization has been denied for this request.';
		}

		$this->getResponse()->representJson(
			$this->_objectManager->create('Magento\Framework\Json\Helper\Data')->jsonEncode($resonseData)
		);

	}

}
