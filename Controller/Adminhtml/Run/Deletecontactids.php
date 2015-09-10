<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Deletecontactids extends \Magento\Backend\App\AbstractAction
{
	protected $messageManager;

	public function __construct(
		\Magento\Backend\App\Action\Context $context
	)
	{
		$this->messageManager = $context->getMessageManager();
		parent::__construct($context);

	}
	public function execute()
	{
		$redirectUrl = $this->getUrl('adminhtml/system_config/edit', array('section' => 'connector_developer_settings'));

		$result = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Resource\Contact')->deleteContactIds();

		$this->messageManager->addSuccess('Number Of Contacts Id Removed: '. $result);

		$this->_redirect($redirectUrl);
	}
}