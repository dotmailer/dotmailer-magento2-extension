<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Studio;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\User\Model\ResourceModel\User as UserResource;

class Disconnect extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::automation_studio';

    /**
     * @var UserResource
     */
    private $userResource;

    /**
     * @param Context $context
     * @param UserResource $userResource
     */
    public function __construct(
        Context $context,
        UserResource $userResource
    ) {
        $this->userResource = $userResource;
        parent::__construct($context);
    }

    /**
     * Disconnect and reset the refresh token.
     *
     * @return void
     */
    public function execute()
    {
        try {
            $adminUser = $this->_auth->getUser();

            /** @var \Magento\User\Model\User $adminUser */
            if ($adminUser->getRefreshToken()) {
                $adminUser->setRefreshToken('');
                $this->userResource->save($adminUser);
            }
            $this->messageManager->addSuccessMessage('Successfully disconnected');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        $this->_redirect('adminhtml/system_config/edit', ['section' => 'connector_developer_settings']);
    }
}
