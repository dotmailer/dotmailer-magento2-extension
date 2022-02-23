<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Order;

use Dotdigitalgroup\Email\Model\ResourceModel\Order;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\Collection as OrderCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;

class MassSetUnprocessed extends \Magento\Backend\App\Action
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
     * @param \Magento\Backend\App\Action\Context $context
     * @param Filter $filter
     * @param Order $orderResource
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
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
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NotFoundException|\Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        if (!$this->isPost($this->getRequest())) {
            throw new \Magento\Framework\Exception\NotFoundException(__('Page not found.'));
        }

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

    /**
     * Check if the request is POST type.
     *
     * @param RequestInterface $request
     *
     * @return bool
     */
    private function isPost(RequestInterface $request)
    {
        /** @var HttpRequest $request */
        return $request->isPost();
    }
}
