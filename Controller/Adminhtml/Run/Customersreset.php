<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Customersreset extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory
     */
    private $contactFactory;

    /**
     * Customersreset constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory $contactFactory
     * @param \Magento\Backend\App\Action\Context                       $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory $contactFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->contactFactory = $contactFactory;
        $this->messageManager = $context->getMessageManager();
        parent::__construct($context);
    }

    /**
     * Refresh suppressed contacts.
     */
    public function execute()
    {
        $this->contactFactory->create()
            ->resetAllContacts();

        $this->messageManager->addSuccessMessage(__('Done.'));

        $redirectUrl = $this->getUrl(
            'adminhtml/system_config/edit',
            ['section' => 'dotdigitalgroup_developer_settings']
        );

        $this->_redirect($redirectUrl);
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dotdigitalgroup_Email::config');
    }
}
