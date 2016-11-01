<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Contact;

use Magento\Backend\App\Action;

class Edit extends \Magento\Backend\App\Action
{
    /**
     * Core registry.
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    protected $_contactFactory;
    /**
     * @var \Magento\Backend\Model\SessionFactory
     */
    protected $_sessionFactory;

    /**
     * Edit constructor.
     *
     * @param \Magento\Backend\Model\SessionFactory $sessionFactory
     * @param \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
     * @param Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Backend\Model\SessionFactory $sessionFactory,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry
    ) {
        $this->_sessionFactory = $sessionFactory;
        $this->_contactFactory = $contactFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dotdigitalgroup_Email::save');
    }

    /**
     * Init actions.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function _initAction()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Dotdigitalgroup_Email::contact')
           ->addBreadcrumb(__('Contact'), __('Contact'))
           ->addBreadcrumb(__('Reports'), __('Reports'));

        return $resultPage;
    }

    /**
     * @return $this|\Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('email_contact_id');
        $model = $this->_contactFactory->create();
        //check the param contact id
        if ($id) {
            //load the and check the contact model
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This contact no longer exists.'));
                /** \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('*/*/');
            }
        }

        $data = $this->_sessionFactory->create()
            ->getFormData(true);
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
