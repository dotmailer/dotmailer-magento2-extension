<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Datafield;

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
		$datafield = $this->getRequest()->getParam('name');
		$type = $this->getRequest()->getParam('type');
		$default = $this->getRequest()->getParam('default');
		$visibility = $this->getRequest()->getParam('visibility');

		$website  = $this->getRequest()->getParam('website', 0);

		$client = $this->_objectManager->create('Dotdigitalgroup\Email\Helper\Data')->getWebsiteApiClient($website);
		if (strlen($datafield)) {
			$response = $client->postDataFields($datafield, $type, $visibility, $default);
			if (isset($response->message))
				$this->messageManager->addError($response->message);
			else
				$this->messageManager->addSuccess('Datafield : '. $datafield . ' created.');
		}

	}

}