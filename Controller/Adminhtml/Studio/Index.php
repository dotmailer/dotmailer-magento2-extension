<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Studio;

class Index extends \Magento\Backend\App\AbstractAction
{
    /**
     * Execute method.
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->renderLayout();

        //not connected - redirect to connect settings page
        $adminUser = $this->_auth->getUser();
        $refreshToken = $adminUser->getRefreshToken();

        if (! $refreshToken) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $this->messageManager->addNoticeMessage('Please enter OAUTH creds and click Connect.');
            $resultRedirect->setPath('admin/system_config/edit', ['section' => 'connector_developer_settings']);

            return $resultRedirect;
        }

    }

    /**
     * Check the permission to run it.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dotdigitalgroup_Email::studio');
    }
}
