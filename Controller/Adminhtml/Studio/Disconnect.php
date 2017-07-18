<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Studio;

class Disconnect extends \Magento\Backend\App\AbstractAction
{
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

        $this->_redirect('*/system_config/*');
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
