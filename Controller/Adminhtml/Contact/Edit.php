<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Contact;

use Magento\Backend\App\Action;

class Edit extends \Magento\Backend\App\Action
{
	/**
	 * Core registry
	 *
	 * @var \Magento\Framework\Registry
	 */
	protected $_coreRegistry = null;

	/**
	 * @var \Magento\Framework\View\Result\PageFactory
	 */
	protected $resultPageFactory;

	/**
	 * @param Action\Context $context
	 * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
	 * @param \Magento\Framework\Registry $registry
	 */
	public function __construct(
		Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory,
		\Magento\Framework\Registry $registry
	) {
		$this->resultPageFactory = $resultPageFactory;
		$this->_coreRegistry = $registry;
		parent::__construct($context);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function _isAllowed()
	{
		return $this->_authorization->isAllowed('Dotdigitalgroup_Email::save');
	}

	/**
	 * Init actions
	 *
	 * @return \Magento\Backend\Model\View\Result\Page
	 */
	protected function _initAction()
	{
		// load layout, set active menu and breadcrumbs
		/** @var \Magento\Backend\Model\View\Result\Page $resultPage */
		$resultPage = $this->resultPageFactory->create();
		$resultPage->setActiveMenu('Dotdigitalgroup_Email::contact')
		           ->addBreadcrumb(__('Contact'), __('Contact'))
		           ->addBreadcrumb(__('Reports'), __('Reports'));
		return $resultPage;
	}

	/**
	 * Edit Blog post
	 *
	 * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public function execute()
	{
		return $this->_redirect('dotdigitalgroup/*/*');
		$id = $this->getRequest()->getParam('email_contact_id');
		$model = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Contact');

		if ($id) {
			$model->load($id);
			if (!$model->getId()) {
				$this->messageManager->addError(__('This contact no longer exists.'));
				/** \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
				$resultRedirect = $this->resultRedirectFactory->create();

				return $resultRedirect->setPath('*/*/');
			}
		}

		$data = $this->_objectManager->get('Magento\Backend\Model\Session')->getFormData(true);
		if (!empty($data)) {
			$model->setData($data);
		}

		$this->_coreRegistry->register('email_contact', $model);

		/** @var \Magento\Backend\Model\View\Result\Page $resultPage */
		$resultPage = $this->_initAction();
		$resultPage->addBreadcrumb(
			$id ? __('Edit Contact ') : __('New Contact'),
			$id ? __('Edit Contact ') : __('New Contact')
		);
		$resultPage->getConfig()->getTitle()->prepend(__('Contacts'));
		$resultPage->getConfig()->getTitle()
		           ->prepend($model->getId() ? $model->getTitle() : __('New Contacts'));

		return $resultPage;
	}
}