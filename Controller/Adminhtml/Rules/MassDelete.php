<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

use Magento\Framework\Controller\ResultFactory;

class MassDelete extends \Magento\Backend\App\Action
{

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Rules
     */
    protected $rules;

    /**
     * MassDelete constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Rules $rules
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Dotdigitalgroup\Email\Model\ResourceModel\Rules $rules
    ) {

        $this->rules = $rules;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $ids = $this->getRequest()->getParam('id');

        if (!is_array($ids)) {
            $this->messageManager->addErrorMessage(__('Please select rules.'));
        } else {
            try {
                $num = $this->rules->massDelete($ids);
                $this->messageManager->addSuccessMessage(__('Total of %1 record(s) were deleted.', $num));
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
