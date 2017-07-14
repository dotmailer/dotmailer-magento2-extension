<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Cron;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    public $resultPageFactory;

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
     * @return bool
     */
    protected function _isAllowed() //@codingStandardsIgnoreLine
    {
        return $this->_authorization->isAllowed('Dotdigitalgroup_Email::cron');
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
