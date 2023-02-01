<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;

class Reviewsync extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::config';

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Dotdigitalgroup\Email\Model\CronFactory
     */
    private $cronFactory;

    /**
     * Reviewsync constructor.
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
     * @return void
     */
    public function execute()
    {
        $result = $this->cronFactory->create()
            ->reviewSync();

        $this->messageManager->addSuccessMessage($result['message']);

        $redirectBack = $this->_redirect->getRefererUrl();
        $this->_redirect($redirectBack);
    }
}
