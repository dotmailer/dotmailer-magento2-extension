<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Importersync extends \Magento\Backend\App\AbstractAction
{
	protected $messageManager;
	protected $_importerFactory;

	public function __construct(
		\Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,

		\Magento\Backend\App\Action\Context $context
	)
	{
		$this->_importerFactory = $importerFactory;
		$this->messageManager = $context->getMessageManager();

		parent::__construct($context);
	}

	/**
	 * Refresh suppressed contacts.
	 */
	public function execute()
	{
		$result =  $this->_importerFactory->create()->processQueue();


		$this->messageManager->addSuccess($result['message']);

		$redirectUrl = $this->getUrl('adminhtml/system_config/edit', array('section' => 'connector_developer_settings'));

		$this->_redirect($redirectUrl);
	}
}