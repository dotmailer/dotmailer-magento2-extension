<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Reviewsreset extends \Magento\Backend\App\AbstractAction
{
	protected $messageManager;
	protected $_reviewFactory;

	public function __construct(
		\Dotdigitalgroup\Email\Model\Resource\ReviewFactory $reviewFactory,
		\Magento\Backend\App\Action\Context $context
	)
	{
		$this->_reviewFactory = $reviewFactory;
		$this->messageManager = $context->getMessageManager();
		parent::__construct($context);

	}

	/**
	 * Refresh suppressed contacts.
	 */
	public function execute()
	{

		$this->_reviewFactory->create()
			->resetReviews();

		$this->messageManager->addSuccess(__('Done.'));

		$redirectUrl = $this->getUrl('adminhtml/system_config/edit', array('section' => 'connector_developer_settings'));

		$this->_redirect($redirectUrl);
	}
}