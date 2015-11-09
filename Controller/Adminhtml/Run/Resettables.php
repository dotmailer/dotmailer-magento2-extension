<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Resettables extends \Magento\Backend\App\AbstractAction
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
	/**
	 * Refresh suppressed contacts.
	 */
	public function execute()
	{
		$this->_contactFactory->create()
			->resetTables();

		$this->messageManager->addSuccess('All tables successfully reset.');

		$redirectUrl = $this->getUrl('adminhtml/system_config/edit', array('section' => 'connector_developer_settings'));

		$this->_redirect($redirectUrl);
	}
}