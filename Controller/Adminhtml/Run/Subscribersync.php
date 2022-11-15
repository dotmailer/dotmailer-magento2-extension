<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

use Dotdigitalgroup\Email\Model\Sync\SubscriberFactory;
use Dotdigitalgroup\Email\Model\Newsletter\UnsubscriberFactory;
use Dotdigitalgroup\Email\Model\Newsletter\ResubscriberFactory;
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
     * @var UnsubscriberFactory
     */
    private $unsubscriberFactory;

    /**
     * @var ResubscriberFactory
     */
    private $resubscriberFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @param SubscriberFactory $subscriberFactory
     * @param UnsubscriberFactory $unsubscriberFactory
     * @param ResubscriberFactory $resubscriberFactory
     * @param Context $context
     */
    public function __construct(
        SubscriberFactory $subscriberFactory,
        UnsubscriberFactory $unsubscriberFactory,
        ResubscriberFactory $resubscriberFactory,
        Context $context
    ) {
        $this->subscriberFactory = $subscriberFactory;
        $this->unsubscriberFactory = $unsubscriberFactory;
        $this->resubscriberFactory = $resubscriberFactory;
        $this->messageManager = $context->getMessageManager();
        parent::__construct($context);
    }

    /**
     * Run Subscriber sync, followed by Unsubscriber and Resubscriber syncs.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $subscriberSyncResult = $this->subscriberFactory->create(
            ['data' => ['web' => true]]
        )->sync();
        $unsubscriberSyncResult = $this->unsubscriberFactory->create()
            ->unsubscribe();
        $resubscriberSyncResult = $this->resubscriberFactory->create()
            ->subscribe();

        $this->messageManager->addSuccessMessage(sprintf(
            '%s. %s %d. %s %d.',
            $subscriberSyncResult['message'],
            'Unsubscribes: ',
            $unsubscriberSyncResult,
            'Resubscribes: ',
            $resubscriberSyncResult
        ));

        /** @var \Magento\Framework\Controller\Result\Redirect $redirect */
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirect->setRefererUrl();
        return $redirect;
    }
}
