<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml;

class Logviewer extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    public $resultPageFactory;

    /**
     * Log viewer constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Dotdigitalgroup_Email::logviewer');
        $resultPage->addBreadcrumb(__('Log Viewer'), __('Log Viewer'));
        $resultPage->addBreadcrumb(__('Reports'), __('Reports'));
        $resultPage->getConfig()->getTitle()->prepend(__('Log Viewer'));

        return $resultPage;
    }

    /**
     * @return bool
     */
    protected function _isAllowed() //@codingStandardsIgnoreLine
    {
        return $this->_authorization->isAllowed('Dotdigitalgroup_Email::logviewer');
    }
}
