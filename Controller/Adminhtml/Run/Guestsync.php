<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

use Magento\Framework\Controller\ResultFactory;
use Dotdigitalgroup\Email\Model\Sync\GuestFactory;

class Guestsync extends \Magento\Backend\App\AbstractAction
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
     * @var GuestFactory
     */
    private $guestFactory;

    /**
     * Guest sync constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param GuestFactory $guestFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        GuestFactory $guestFactory
    ) {
        $this->guestFactory = $guestFactory;
        $this->messageManager = $context->getMessageManager();
        parent::__construct($context);
    }

    /**
     * Run Guest sync.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $result = $this->guestFactory->create(
            ['data' => ['web' => true]]
        )->sync();

        $this->messageManager->addSuccessMessage($result['message']);

        /** @var \Magento\Framework\Controller\Result\Redirect $redirect */
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirect->setRefererUrl();
        return $redirect;
    }
}
