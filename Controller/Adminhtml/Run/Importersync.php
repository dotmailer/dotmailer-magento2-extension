<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Importersync extends \Magento\Backend\App\AbstractAction
{
	protected $messageManager;
	protected $_proccessorFactory;

	public function __construct(
		\Dotdigitalgroup\Email\Model\ProccessorFactory $proccessorFactory,

		\Magento\Backend\App\Action\Context $context
	)
	{
		$this->_proccessorFactory = $proccessorFactory;
		$this->messageManager = $context->getMessageManager();

		parent::__construct($context);
	}

	/**
	 * Refresh suppressed contacts.
	 */
	public function execute()
	{
		$result =  $this->_proccessorFactory->create()->processQueue();


		$this->messageManager->addSuccess($result['message']);

		$redirectUrl = $this->getUrl('adminhtml/system_config/edit', array('section' => 'connector_developer_settings'));

		$this->_redirect($redirectUrl);
	}
}