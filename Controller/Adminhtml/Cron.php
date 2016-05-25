<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml;

class Cron extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * Cron constructor.
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
        $resultPage->setActiveMenu('Dotdigitalgroup_Email::cron');
        $resultPage->addBreadcrumb(__('Cron'), __('Cron'));
        $resultPage->addBreadcrumb(__('Reports'), __('Reports'));
        $resultPage->getConfig()->getTitle()->prepend(__('Cron Tasks'));

        return $resultPage;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dotdigitalgroup_Email::cron');
    }
}
