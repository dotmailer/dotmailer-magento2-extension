<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Addressbook;

class Save extends \Magento\Backend\App\AbstractAction
{
	protected $messageManager;
	protected $_helper;

	public function __construct(
		\Magento\Backend\App\Action\Context $context

	)
	{
		$this->messageManager = $context->getMessageManager();
		parent::__construct($context);

	}
	public function execute()
	{
		$addressBookName = $this->getRequest()->getParam('name');
		$visibility = $this->getRequest()->getParam('visibility');
		$website  = $this->getRequest()->getParam('website', 0);

		$client = $this->_objectManager->create('Dotdigitalgroup\Email\Helper\Data')->getWebsiteApiClient($website);
		if (strlen($addressBookName)) {
			$response = $client->postAddressBooks($addressBookName, $visibility);
			if (isset($response->message))
				$this->messageManager->addError($response->message);
			else
				$this->messageManager->addSuccess('Address book : '. $addressBookName . ' created.');
		}

	}

}