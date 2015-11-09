<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Ordersreset extends \Magento\Backend\App\AbstractAction
{
	protected $messageManager;
	protected $_orderFactory;

	public function __construct(
		\Dotdigitalgroup\Email\Model\Resource\OrderFactory $orderFactory,
		\Magento\Backend\App\Action\Context $context
	)
	{
		$this->_orderFactory = $orderFactory;
		$this->messageManager = $context->getMessageManager();
		parent::__construct($context);

	}

	/**
	 * Refresh suppressed contacts.
	 */
	public function execute()
	{
		$this->_orderFactory->create()
			->resetOrders();

		$this->messageManager->addSuccess(__('Done.'));

		$redirectUrl = $this->getUrl('adminhtml/system_config/edit', array('section' => 'connector_developer_settings'));

		$this->_redirect($redirectUrl);
	}
}