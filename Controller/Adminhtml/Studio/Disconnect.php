<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Studio;

class Disconnect extends \Magento\Backend\App\AbstractAction
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::studio';

    /**
     * Disconnect and remote the refresh token.
     *
     * @return void
     */
    public function execute()
    {
        try {
            $adminUser = $this->_auth->getUser();

            if ($adminUser->getRefreshToken()) {
                $adminUser->setRefreshToken('')
                    ->save();
            }
            $this->messageManager->addSuccessMessage('Successfully disconnected');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        $this->_redirect('adminhtml/system_config/edit', ['section' => 'connector_developer_settings']);
    }
}
