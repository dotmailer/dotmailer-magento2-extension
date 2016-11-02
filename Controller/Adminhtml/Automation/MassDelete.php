<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Automation;

use Magento\Framework\Controller\ResultFactory;

class MassDelete extends \Magento\Backend\App\Action
{

    /**
     * @var \Dotdigitalgroup\Email\Model\AutomationFactory
     */
    public $automation;
    /**
     * MassDelete constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\AutomationFactory $automation
     * @param \Magento\Backend\App\Action\Context            $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\AutomationFactory $automation,
        \Magento\Backend\App\Action\Context $context
    )
    {
        $this->automation = $automation;

        parent::__construct($context);
    }
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $searchIds = $this->getRequest()->getParam('id');
        if (!is_array($searchIds)) {
            $this->messageManager->addErrorMessage(__('Please select automation.'));
        } else {
            try {
                //@codingStandardsIgnoreStart
                foreach ($searchIds as $searchId) {
                    $model = $this->automation->setId($searchId);
                    $model->delete();
                }
                //@codingStandardsIgnoreEnd
                $this->messageManager->addSuccessMessage(__('Total of %1 record(s) were deleted.', count($searchIds)));
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
