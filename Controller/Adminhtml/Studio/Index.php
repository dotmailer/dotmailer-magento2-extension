<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Studio;

class Index extends \Magento\Backend\App\AbstractAction
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::studio';

    /**
     * Execute method.
     */
    public function execute()
    {
        //not connected - redirect to connect settings page
        $adminUser = $this->_auth->getUser();
        $refreshToken = $adminUser->getRefreshToken();

        if (! $refreshToken) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $this->messageManager->addNoticeMessage('Please enter OAUTH creds and click Connect.');
            //Redirect to developer section config
            $resultRedirect->setPath('adminhtml/system_config/edit', ['section' => 'connector_developer_settings']);

            return $resultRedirect;
        }

        //Load and render layout if there is $refreshToken
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
