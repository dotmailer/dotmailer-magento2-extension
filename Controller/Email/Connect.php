<?php

namespace Dotdigitalgroup\Email\Controller\Email;


class Connect extends \Magento\Framework\App\Action\Action
{

	public function execute()
	{

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