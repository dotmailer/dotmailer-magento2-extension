<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

use Dotdigitalgroup\Email\Model\Sync\OrderFactory;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;

class Ordersync extends Action implements HttpGetActionInterface
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
     * @var OrderFactory
     */
    private $syncOrderFactory;

    /**
     * @param OrderFactory $syncOrderFactory
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        OrderFactory $syncOrderFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->syncOrderFactory    = $syncOrderFactory;
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
        $result = $this->syncOrderFactory->create(
            ['data' => ['web' => true]]
        )->sync();

        $this->messageManager->addSuccessMessage($result['message']);

        // @codingStandardsIgnoreStart
        $redirectBack = $this->_redirect->getRefererUrl();
        $this->_redirect($redirectBack);
        // @codingStandardsIgnoreEnd
    }
}
