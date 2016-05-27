<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Subscribersreset extends \Magento\Backend\App\AbstractAction
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
     * Subscribersreset constructor.
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
     * Refresh suppressed contacts.
     */
    public function execute()
    {
        $this->_contactFactory->create()
            ->resetSubscribers();

        $this->messageManager->addSuccess(__('Done.'));

        $redirectUrl = $this->getUrl('adminhtml/system_config/edit', ['section' => 'connector_developer_settings']);

        $this->_redirect($redirectUrl);
    }
}
