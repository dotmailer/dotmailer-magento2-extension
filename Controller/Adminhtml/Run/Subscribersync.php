<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

use Dotdigitalgroup\Email\Model\Sync\SubscriberFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;

class Subscribersync extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::config';

    /**
     * @var SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @param SubscriberFactory $subscriberFactory
     * @param Context $context
     */
    public function __construct(
        SubscriberFactory $subscriberFactory,
        Context $context
    ) {
        $this->subscriberFactory = $subscriberFactory;
        $this->messageManager = $context->getMessageManager();
        parent::__construct($context);
    }

    /**
     * Run Subscriber sync.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $subscriberSyncResult = $this->subscriberFactory->create(
            ['data' => ['web' => true]]
        )->sync();

        $this->messageManager->addSuccessMessage($subscriberSyncResult['message']);

        /** @var \Magento\Framework\Controller\Result\Redirect $redirect */
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirect->setRefererUrl();
        return $redirect;
    }
}
