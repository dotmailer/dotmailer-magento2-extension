<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Deletecontactids extends \Magento\Backend\App\AbstractAction
{
	protected $messageManager;
	protected $_contactFactory;

	public function __construct(
		\Dotdigitalgroup\Email\Model\Resource\ContactFactory $contactFactory,
		\Magento\Backend\App\Action\Context $context
	)
	{
		$this->_contactFactory = $contactFactory;
		$this->messageManager = $context->getMessageManager();
		parent::__construct($context);

	}
	public function execute()
	{
		$redirectUrl = $this->getUrl('adminhtml/system_config/edit', array('section' => 'connector_developer_settings'));

		$result = $this->_contactFactory->create()
			->deleteContactIds();

		$this->messageManager->addSuccess('Number Of Contacts Id Removed: '. $result);

		$this->_redirect($redirectUrl);
	}
}