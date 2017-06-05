<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Importer;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

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
     * Check the permission to run it.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dotdigitalgroup_Email::importer');
    }

    /**
     * Index action.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        //Call page factory to render layout and page content
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        //Set the menu which will be active for this page
        $resultPage->setActiveMenu('Dotdigitalgroup_Email::importer');
        //Set the header title of grid
        $resultPage->getConfig()->getTitle()->prepend(__('Import Report'));

        $resultPage->addBreadcrumb(__('Report'), __('Report'));
        $resultPage->addBreadcrumb(__('Importer'), __('Importer'));

        return $resultPage;
    }
}
