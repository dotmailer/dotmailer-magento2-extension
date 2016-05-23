<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Contactsync extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\CronFactory
     */
    protected $_cronFactory;

    /**
     * Contactsync constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\CronFactory $cronFactory
     * @param \Magento\Backend\App\Action\Context      $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\CronFactory $cronFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_cronFactory = $cronFactory;
        $this->messageManager = $context->getMessageManager();
        parent::__construct($context);
    }

    /**
     * Refresh suppressed contacts.
     */
    public function execute()
    {
        $result = $this->_cronFactory->create()
            ->contactSync();

        $this->messageManager->addSuccess($result['message']);

        $redirectUrl = $this->getUrl('adminhtml/system_config/edit', ['section' => 'connector_developer_settings']);

        $this->_redirect($redirectUrl);
    }
}
