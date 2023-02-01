<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Order;

use Dotdigitalgroup\Email\Model\ResourceModel\Order;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\Collection as OrderCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Ui\Component\MassAction\Filter;

class MassSetUnprocessed extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::order';

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var Order
     */
    private $orderResource;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * MassSetProcessed constructor.
     *
     * @param Context $context
     * @param Filter $filter
     * @param Order $orderResource
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        Order $orderResource,
        CollectionFactory $collectionFactory
    ) {
        $this->filter = $filter;
        $this->orderResource = $orderResource;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }

    /**
     * Execute.
     *
     * @return Redirect
     * @throws \Magento\Framework\Exception\NotFoundException|\Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collectionSize = $collection->getSize();

        /** @var OrderCollection $collection */
        $this->orderResource->setUnProcessed($collection->getAllOrderIds());

        $this->messageManager->addSuccessMessage(
            __(
                'A total of %1 record(s) have been set as not processed.',
                $collectionSize
            )
        );

        return $resultRedirect->setPath('*/*/');
    }
}
