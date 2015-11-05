<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Customersreset extends \Magento\Backend\App\AbstractAction
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
	public function executeInternal()
	{
		$this->_contactFactory->create()
			->resetAllContacts();

		$this->messageManager->addSuccess(__('Done.'));

		$redirectUrl = $this->getUrl('adminhtml/system_config/edit', array('section' => 'connector_developer_settings'));

		$this->_redirect($redirectUrl);
	}
}