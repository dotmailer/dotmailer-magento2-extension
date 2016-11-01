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
            $this->messageManager->addErrorMessage(__('Please select campaigns.'));
        } else {
            try {
                foreach ($searchIds as $searchId) {
                    //@codingStandardsIgnoreStart
                    $model = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Campaign')->setId($searchId);
                    $model->delete();
                    //@codingStandardsIgnoreEnd
                }
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
