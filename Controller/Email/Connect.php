<?php

namespace Dotdigitalgroup\Email\Controller\Email;


class Disconnect extends \Dotdigitalgroup\Email\Controller\Response
{

	/**
	 * Basket page to display the user items with specific email.
	 */
	public function execute()
	{
		//authenticate
		$this->authenticate();

		/**
		 * Disconnect and remote the refresh token.
		 */
		try {
			$adminUser = $this->_objectManager->get('Magento\Backend\Model\Session')->getUser();

			//save token
			if ($adminUser->getRefreshToken()) {
				$adminUser->setRefreshToken()
					->save();
			}
			$this->messageManager->addSuccess('Successfully disconnected');
		}catch (\Exception $e){

			$this->messageManager->addError($e->getMessage());
		}

	//	$this->_redirectReferer('*/*/*');
	}
}