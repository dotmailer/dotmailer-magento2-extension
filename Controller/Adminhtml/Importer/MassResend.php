<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Importer;

use Dotdigitalgroup\Email\Controller\Adminhtml\Importer as ImporterController;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class MassResend
 * @package Dotdigitalgroup\Email\Controller\Adminhtml\Importer
 */
class MassResend extends ImporterController
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    public $importerFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Importer
     */
    public $importerResource;

    /**
     * MassResend constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importer
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importer
    ) {
        $this->importerResource = $importer;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $searchIds = $this->getRequest()->getParam('id');
        if (!is_array($searchIds)) {
            $this->messageManager->addErrorMessage(__('Please select importer.'));
        } else {
            try {
                $num = $this->importerResource->massResend($searchIds);
                $this->messageManager->addSuccessMessage(__('Total of %1 record(s) were reset.', $num));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/');

        return $resultRedirect;
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
}
