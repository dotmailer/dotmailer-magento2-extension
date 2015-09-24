<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Contact;

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

	public function __construct(
		\Magento\Backend\App\Action\Context $context,
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
		return true;
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
		           ->addBreadcrumb(__('Manage Contact'), __('Manage Contact'));
		return $resultPage;
	}

	/**
	 * Edit grid record
	 *
	 * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public function execute()
	{
		// 1. Get ID and create model
		$contactId = $this->getRequest()->getParam('email_contact_id');
		$contactModel = $this->_objectManager->create('\Dotdigitalgroup\Email\Model\Contact');

		// 2. Initial checking
		if ($contactId) {
			$contactModel->load($contactId);
			if (!$contactModel->getId()) {
				$this->messageManager->addError(__('This contact record no longer exists.'));
				/** \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
				$resultRedirect = $this->resultRedirectFactory->create();

				return $resultRedirect->setPath('*/*/');
			}
		}

		$this->_objectManager->create('Dotdigitalgroup\Email\Model\Apiconnector\Contact')->syncContact($contactId);

		$resultRedirect = $this->resultRedirectFactory->create();

		// 3. Set entered data if was error when we do save
		$data = $this->_objectManager->get('Magento\Backend\Model\Session')->getFormData(true);
		if (!empty($data)) {
			$contactModel->setData($data);
		}

		// 4. Register model to use later in blocks
		$this->_coreRegistry->register('email_contact_data', $contactModel);

		// 5. Build edit form
		/** @var \Magento\Backend\Model\View\Result\Page $resultPage */
		$resultPage = $this->_initAction();
		$resultPage->addBreadcrumb(
			$contactId ? __('Edit Post') : __('New Contact'),
			$contactId ? __('Edit Post') : __('New Contact')
		);
		$resultPage->getConfig()->getTitle()->prepend(__('Contacts'));
		$resultPage->getConfig()->getTitle()
			->prepend($contactModel->getId() ? $contactModel->getTitle() : __('New Contact'));

		return $resultPage;
	}
}