<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Cron;

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
		return $this->_authorization->isAllowed('Dotdigitalgroup_Email::cron');
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
		$resultPage->setActiveMenu('Dotdigitalgroup_Email::cron');
		$resultPage->addBreadcrumb(__('Cron'), __('Cron '));
		$resultPage->addBreadcrumb(__('Cron'), __('Cron'));
		$resultPage->getConfig()->getTitle()->prepend(__('Cron Scheldule Report'));

		return $resultPage;
	}
}
