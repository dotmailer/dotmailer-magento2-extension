<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Wishlist;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends \Magento\Backend\App\Action
{
	/**
	 * @var PageFactory
	 */
	protected $resultPageFactory;

	/**
	 * @param Context $context
	 * @param PageFactory $resultPageFactory
	 */
	public function __construct(
		Context $context,
		PageFactory $resultPageFactory
	) {
		parent::__construct($context);
		$this->resultPageFactory = $resultPageFactory;
	}
	/**
	 * Check the permission to run it
	 *
	 * @return bool
	 */
	protected function _isAllowed()
	{
		return $this->_authorization->isAllowed('Dotdigitalgroup_Email::wishlist');
	}

	/**
	 * Index action
	 *
	 * @return \Magento\Backend\Model\View\Result\Page
	 */
	public function executeInternal()
	{

		/** @var \Magento\Backend\Model\View\Result\Page $resultPage */
		$resultPage = $this->resultPageFactory->create();
		$resultPage->setActiveMenu('Dotdigitalgroup_Email::wishlist');
		$resultPage->addBreadcrumb(__('Wishlists'), __('Wishlists'));
		$resultPage->getConfig()->getTitle()->prepend(__('Wishlist Report'));

		return $resultPage;
	}
}