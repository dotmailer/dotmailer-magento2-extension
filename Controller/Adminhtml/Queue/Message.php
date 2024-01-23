<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Queue;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Dotdigitalgroup\Email\Ui\DataProvider\Queue\Listing\CollectionFactory as QueueCollectionFactory;

class Message extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::queue';

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var QueueCollectionFactory
     */
    private $queueCollectionFactory;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param QueueCollectionFactory $queueCollectionFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        QueueCollectionFactory $queueCollectionFactory
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->queueCollectionFactory = $queueCollectionFactory;
        parent::__construct($context);
    }

    /**
     * Execute.
     *
     * @return Json
     */
    public function execute(): Json
    {
        $messageId = $this->getRequest()->getParam('message_id');

        $message = $this->queueCollectionFactory
            ->create()
            ->loadMessageById($messageId);

        if ($message->getSize()) {
            $data = $message->getFirstItem()->getBody();
        } else {
            $data = '';
        }
        $resultJson = $this->jsonFactory->create();
        $resultJson->setData($data);

        return $resultJson;
    }
}
