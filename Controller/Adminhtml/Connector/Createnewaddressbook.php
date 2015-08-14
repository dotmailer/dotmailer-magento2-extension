<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Connector;


class Createnewaddressbook extends \Magento\Backend\App\Action
{

	public function __construct(
		\Dotdigitalgroup\Email\Model\Apiconnector\Rest $rest
	)
	{
		$this->rest = $rest;

	}

	/**
	 * Validate api user.
	 */
	public function execute()
	{

		var_dump('create new addressbook');
		die;
		$addressBookName    = $this->getRequest()->getParam('name', false);
		$visibility         = $this->getRequest()->getParam('visibility', 'private');

		//@todo send the website id

		if (strlen($addressBookName) && $addressBookName) {


			$response = $this->rest->postAddressBooks($addressBookName, $visibility);


			//$response = $client->postAddressBooks($addressBookName, $visibility);
			//if (isset($response->message))
				//Mage::getSingleton('adminhtml/session')->addError($response->message);
			//else
				//Mage::getSingleton('adminhtml/session')->addSuccess('Address book : '. $addressBookName . ' created.');
		}


	}




}
