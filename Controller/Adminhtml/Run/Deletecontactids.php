<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Deletecontactids extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory
     */
    protected $_contactFactory;

    /**
     * Deletecontactids constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory $contactFactory
     * @param \Magento\Backend\App\Action\Context                  $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory $contactFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_contactFactory = $contactFactory;
        $this->messageManager = $context->getMessageManager();
        parent::__construct($context);
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $redirectUrl = $this->getUrl('adminhtml/system_config/edit', ['section' => 'connector_developer_settings']);

        $result = $this->_contactFactory->create()
            ->deleteContactIds();

        $this->messageManager->addSuccess('Number Of Contacts Id Removed: '.$result);

        $this->_redirect($redirectUrl);
    }
}
