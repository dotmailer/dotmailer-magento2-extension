<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Suppresscontacts extends \Magento\Backend\App\AbstractAction
{
	protected $messageManager;
	protected $_subscriberFactory;

	public function __construct(
		\Dotdigitalgroup\Email\Model\Newsletter\SubscriberFactory $subscriberFactory,
		\Magento\Backend\App\Action\Context $context
	)
	{
		$this->_subscriberFactory = $subscriberFactory;
		$this->messageManager = $context->getMessageManager();
		parent::__construct($context);

	}
	/**
	 * Refresh suppressed contacts.
	 */
	public function executeInternal()
	{
		$this->_subscriberFactory->create()
		    ->unsubscribe(true);
		$this->messageManager->addSuccess('Done.');

		$redirectUrl = $this->getUrl('adminhtml/system_config/edit', array('section' => 'connector_developer_settings'));

		$this->_redirect($redirectUrl);
	}
}