<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Resettables extends \Magento\Backend\App\AbstractAction
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
		$this->_objectManager->create('Dotdigitalgroup\Email\Model\Resource\Contact')->resetTables();

		$this->messageManager->addSuccess('All tables successfully reset.');

		$redirectUrl = $this->getUrl('adminhtml/system_config/edit', array('section' => 'connector_developer_settings'));

		$this->_redirect($redirectUrl);
	}
}