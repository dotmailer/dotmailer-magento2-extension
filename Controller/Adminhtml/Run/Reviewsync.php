<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Reviewsync extends \Magento\Backend\App\AbstractAction
{
	protected $messageManager;

	public function __construct(
		\Magento\Backend\App\Action\Context $context
	)
	{
		$this->messageManager = $context->getMessageManager();
		parent::__construct($context);

	}

	/**
	 * Refresh suppressed contacts.
	 */
	public function execute()
	{
		$result = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Cron')->reviewSync();

		$this->messageManager->addSuccess($result['message']);

		$redirectUrl = $this->getUrl('adminhtml/system_config/edit', array('section' => 'connector_developer_settings'));

		$this->_redirect($redirectUrl);
	}
}