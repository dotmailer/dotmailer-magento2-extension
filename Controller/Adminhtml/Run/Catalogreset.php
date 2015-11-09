<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Catalogreset extends \Magento\Backend\App\AbstractAction
{
	protected $messageManager;
	protected $_catalogFactory;

	public function __construct(
		\Dotdigitalgroup\Email\Model\Resource\CatalogFactory $catalogFactory,
		\Magento\Backend\App\Action\Context $context
	)
	{
		$this->_catalogFacotry = $catalogFactory;
		$this->messageManager = $context->getMessageManager();
		parent::__construct($context);

	}

	/**
	 * Refresh suppressed contacts.
	 */
	public function execute()
	{

		$this->_catalogFacotry->create()
			->resetCatalog();

		$this->messageManager->addSuccess(__('Done.'));

		$redirectUrl = $this->getUrl('adminhtml/system_config/edit', array('section' => 'connector_developer_settings'));

		$this->_redirect($redirectUrl);
	}
}