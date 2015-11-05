<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Quotesreset extends \Magento\Backend\App\AbstractAction
{
	protected $messageManager;
	protected $_quoteFactory;

	public function __construct(
		\Dotdigitalgroup\Email\Model\Resource\QuoteFactory $quoteFactory,
		\Magento\Backend\App\Action\Context $context
	)
	{
		$this->_quoteFactory = $quoteFactory;
		$this->messageManager = $context->getMessageManager();
		parent::__construct($context);

	}

	/**
	 * Refresh suppressed contacts.
	 */
	public function executeInternal()
	{
		$this->_quoteFactory->create()
			->resetQuotes();

		$this->messageManager->addSuccess(__('Done.'));

		$redirectUrl = $this->getUrl('adminhtml/system_config/edit', array('section' => 'connector_developer_settings'));

		$this->_redirect($redirectUrl);
	}
}