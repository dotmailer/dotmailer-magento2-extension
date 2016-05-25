<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Campaign;

use Dotdigitalgroup\Email\Controller\Adminhtml\Campaign as CampaignController;
use Magento\Framework\Controller\ResultFactory;

class MassDelete extends CampaignController
{
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $searchIds = $this->getRequest()->getParam('id');

        if (!is_array($searchIds)) {
            $this->messageManager->addError(__('Please select campaigns.'));
        } else {
            try {
                foreach ($searchIds as $searchId) {
                    $model = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Campaign')->load($searchId);
                    $model->delete();
                }
                $this->messageManager->addSuccess(__('Total of %1 record(s) were deleted.', count($searchIds)));
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
