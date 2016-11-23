<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Deletecontactids extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    public $messageManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory
     */
    public $contactFactory;

    /**
     * Deletecontactids constructor.
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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $redirectUrl = $this->getUrl('adminhtml/system_config/edit', ['section' => 'connector_developer_settings']);

        $result = $this->contactFactory->create()
            ->deleteContactIds();

        $this->messageManager->addSuccessMessage('Contact id\'s reseted ' . $result);

        $this->_redirect($redirectUrl);
    }
}
