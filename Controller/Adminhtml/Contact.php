<?php
namespace Dotdigitalgroup\Email\Controller\Adminhtml;

//use Magento\Backend\App\Action;
//use Magento\Framework\Controller\ResultFactory;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Contact extends \Magento\Backend\App\Action
{
	const ADMIN_RESOURCE = 'Ashsmith_Blog::post';

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
	 * Index action
	 *
	 * @return \Magento\Backend\Model\View\Result\Page
	 */
	public function execute()
	{
		/** @var \Magento\Backend\Model\View\Result\Page $resultPage */
		$resultPage = $this->resultPageFactory->create();
		$resultPage->setActiveMenu('Dotdigitalgroup_Email::contact');
		$resultPage->addBreadcrumb(__('Contacts'), __('Contacts'));
		$resultPage->addBreadcrumb(__('Reports'), __('Reports'));
		$resultPage->getConfig()->getTitle()->prepend(__('Contacts'));

		return $resultPage;
	}


	/**
	 * @return bool
	 */
	//protected function _isAllowed()
//	{
//		return $this->_authorization->isAllowed('Dotdigitalgroup_Email::contact');
//	}
}