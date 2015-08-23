<?php

namespace Dotdigitalgroup\Email\Controller\Index;

use GuzzleHttp;
use \Dotdigitalgroup\Email\Model\Cron;

class Index extends \Magento\Framework\App\Action\Action {

	/**
	 * Pass arguments for dependency injection
	 *
	 * @param \Magento\Framework\App\Action\Context $context
	 */
	public function __construct(
		\Magento\Framework\App\Action\Context $context
	) {
		parent::__construct($context);
	}

	/**
	 * Sets the content of the response
	 */
	public function execute() {

		$model = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Cron');
		$model = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Cron');

		//$model->contactSync();
		//$model->emailImporter();
		//$model->subscribersAndGuestSync();
		//$model->catalogSync();
		//$model->abandonedCarts();
		$model->orderSync();


		return;

		echo $this->getViewFileUrl('Dotdigitalgroup_Email/images/i_msg-error.gif');

		$rest = new \Dotdigitalgroup\Email\Model\Apiconnector\Rest();

		$apiUsername = 'apiuser-e7b76c151df7@apiconnector.com';
		$pass = 'admin12d33';

		//$res = $client->get('https://apiconnector.com/v2/account-info', ['auth' =>  ['apiuser-e7b76c151df7@apiconnector.com', 'admin123']]);
		$res = $rest->validate($apiUsername, $pass);
		//echo $res->getStatusCode();
		// "200"

		//echo  $rest->getBody();

		///$this->getResponse()->setContent('Home page.');
	}
}