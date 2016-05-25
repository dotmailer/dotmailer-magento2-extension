<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Order;

use Dotdigitalgroup\Email\Controller\Adminhtml\Order as OrderController;
use Magento\Framework\Controller\ResultFactory;

class MassDelete extends OrderController
{
    protected $messageManager;
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $ids = $this->getRequest()->getParam('email_order_id');
        if (!is_array($ids)) {
            $this->messageManager->addError(__('Please select orders.'));
        } else {
            try {
                foreach ($ids as $id) {
                    $model = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Order')->load($id);
                    $model->delete();
                }
                $this->messageManager->addSuccess(__('Total of %1 record(s) were deleted.', count($ids)));
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/');

        return $resultRedirect;
    }
}
