<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Wishlistsync extends \Magento\Backend\App\AbstractAction
{
	protected $messageManager;
	protected $_wishlistFactory;

	public function __construct(
		\Dotdigitalgroup\Email\Model\Sync\WishlistFactory $wishlistFactory,
		\Magento\Backend\App\Action\Context $context
	)
	{
		$this->_wishlistFactory = $wishlistFactory;
		$this->messageManager = $context->getMessageManager();
		parent::__construct($context);

	}

	/**
	 * Refresh suppressed contacts.
	 */
	public function execute()
	{
		$result = $this->_wishlistFactory->create()
			->sync();

		$this->messageManager->addSuccess($result['message']);

		$redirectUrl = $this->getUrl('adminhtml/system_config/edit', array('section' => 'connector_developer_settings'));

		$this->_redirect($redirectUrl);
	}
}