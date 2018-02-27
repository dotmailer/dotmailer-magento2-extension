<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Cron;

class Index extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::cron';

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $resultPageFactory;

    /**
     * Index constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {

        //Call page factory to render layout and page content
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        //Set the menu which will be active for this page
        $resultPage->setActiveMenu('Dotdigitalgroup_Email::cron');
        //Set the header title of grid
        $resultPage->getConfig()->getTitle()->prepend(__('Cron Tasks'));

        $resultPage->addBreadcrumb(__('Report'), __('Report'));
        $resultPage->addBreadcrumb(__('Cron'), __('Cron'));

        return $resultPage;
    }
}
