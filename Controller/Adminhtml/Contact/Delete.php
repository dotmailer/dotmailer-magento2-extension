<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Contact;

class Delete extends \SweetTooth\Webhook\Controller\Adminhtml\System\Webhook
{
    /**
     * Delete Action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $webhook = $this->_initWebhook();
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($webhook->getId()) {
            try {
                $webhook->delete();
                $this->messageManager->addSuccess(__('You deleted the webhook.'));
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('adminhtml/*/edit', ['_current' => true]);
            }
        }
        return $resultRedirect->setPath('adminhtml/*/');
    }
}
