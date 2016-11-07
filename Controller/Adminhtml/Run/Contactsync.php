<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Contactsync extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    public $messageManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\CronFactory
     */
    public $cronFactory;

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
        $this->cronFactory    = $cronFactory;
        $this->messageManager = $context->getMessageManager();
        parent::__construct($context);
    }

    /**
     * Refresh suppressed contacts.
     */
    public function execute()
    {
        $result = $this->cronFactory->create()
            ->contactSync();

        $this->messageManager->addSuccessMessage($result['message']);

        $redirectBack = $this->_redirect->getRefererUrl();

        $this->_redirect($redirectBack);
    }
}
