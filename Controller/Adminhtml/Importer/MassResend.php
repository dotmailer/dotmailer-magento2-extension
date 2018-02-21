<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Importer;

use Dotdigitalgroup\Email\Controller\Adminhtml\Importer as ImporterController;
use Magento\Framework\Controller\ResultFactory;

class MassResend extends ImporterController
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::importer';

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Importer
     */
    private $importerResource;
    
    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * MassResend constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importer
     * @param \Magento\Framework\Escaper $escaper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importer,
        \Magento\Framework\Escaper $escaper
    ) {
        $this->importerResource = $importer;
        $this->escaper = $escaper;
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
}
