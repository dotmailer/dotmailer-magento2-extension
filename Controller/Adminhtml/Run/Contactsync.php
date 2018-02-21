<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Contactsync extends \Magento\Backend\App\AbstractAction
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::config';

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Dotdigitalgroup\Email\Model\CronFactory
     */
    private $cronFactory;

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
     *
     * @return null
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
