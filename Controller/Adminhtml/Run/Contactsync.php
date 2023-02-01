<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

use Dotdigitalgroup\Email\Model\Sync\CustomerFactory;
use Dotdigitalgroup\Email\Model\Sync\GuestFactory;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;

class Contactsync extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::config';

    /**
     * @var \Dotdigitalgroup\Email\Model\CronFactory
     */
    private $cronFactory;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var GuestFactory
     */
    private $guestFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @param \Dotdigitalgroup\Email\Model\CronFactory $cronFactory
     * @param CustomerFactory $customerFactory
     * @param GuestFactory $guestFactory
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\CronFactory $cronFactory,
        CustomerFactory $customerFactory,
        GuestFactory $guestFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->cronFactory = $cronFactory;
        $this->customerFactory = $customerFactory;
        $this->guestFactory = $guestFactory;
        $this->messageManager = $context->getMessageManager();
        parent::__construct($context);
    }

    /**
     * Run a combined 'contact' sync.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $customerSyncResult = $this->customerFactory->create(
            ['data' => ['web' => true]]
        )->sync();
        $subscriberSyncResult = $this->cronFactory->create()->subscriberSync();
        $guestSyncResult = $this->guestFactory->create(
            ['data' => ['web' => true]]
        )->sync();

        $this->messageManager->addSuccessMessage(sprintf(
            '%s . %s . %s',
            $customerSyncResult['message'],
            $subscriberSyncResult['message'],
            $guestSyncResult['message']
        ));

        /** @var \Magento\Framework\Controller\Result\Redirect $redirect */
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirect->setRefererUrl();
        return $redirect;
    }
}
