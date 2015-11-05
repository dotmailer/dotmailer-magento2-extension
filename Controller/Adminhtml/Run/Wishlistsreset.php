<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Wishlistsreset extends \Magento\Backend\App\AbstractAction
{
	protected $messageManager;

	protected $_wishlistFactory;


	public function __construct(
		\Dotdigitalgroup\Email\Model\Resource\WishlistFactory $wishlistFactory,
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
	public function executeInternal()
	{

		$this->_wishlistFactory->create()
			->resetWishlists();

		$this->messageManager->addSuccess(__('Done.'));

		$redirectUrl = $this->getUrl('adminhtml/system_config/edit', array('section' => 'connector_developer_settings'));

		$this->_redirect($redirectUrl);
	}
}