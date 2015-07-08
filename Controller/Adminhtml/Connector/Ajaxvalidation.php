<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Connector;

class Ajaxvalidation extends \Magento\Backend\App\Action
{


	/**
	 * Validate api user.
	 */
	public function execute()
	{
		$params = $this->getRequest()->getParams();
		$apiUsername     = $params['api_username'];
		$apiPassword     = base64_decode($params['api_password']);

		//validate api, check against account info.
		$rest = new \Dotdigitalgroup\Email\Model\Apiconnector\Rest();
		$result = $rest->validate($apiUsername, $apiPassword);

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
