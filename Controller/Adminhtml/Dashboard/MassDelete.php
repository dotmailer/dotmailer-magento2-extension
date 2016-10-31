<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Dashboard;

use Magento\Framework\Controller\ResultFactory;

class MassDelete extends \Magento\Backend\App\Action
{
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $ids = $this->getRequest()->getParam('id');

        if (!is_array($ids)) {
            $this->messageManager->addError(__('Please select cron.'));
        } else {
            try {
                foreach ($ids as $id) {
                    //@codingStandardsIgnoreStart
                    $model = $this->_objectManager->create('Magento\Cron\Model\Schedule')->setId($id);
                    $model->delete();
                    //@codingStandardsIgnoreEnd
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
