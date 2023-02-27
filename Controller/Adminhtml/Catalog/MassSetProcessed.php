<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Catalog;

use Dotdigitalgroup\Email\Model\ResourceModel\Catalog;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog\Collection as CatalogCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Ui\Component\MassAction\Filter;

class MassSetProcessed extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::catalog';

    /**
     * @var Catalog
     */
    private $collectionResource;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var MessageManagerInterface
     */
    protected $messageManager;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * MassSetProcessed constructor.
     *
     * @param Context $context
     * @param ResultFactory $resultFactory
     * @param MessageManagerInterface $messageManager
     * @param Filter $filter
     * @param Catalog $collectionResource
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        ResultFactory $resultFactory,
        MessageManagerInterface $messageManager,
        Filter $filter,
        Catalog $collectionResource,
        CollectionFactory $collectionFactory
    ) {
        $this->resultFactory = $resultFactory;
        $this->messageManager = $messageManager;
        $this->filter = $filter;
        $this->collectionResource = $collectionResource;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return Redirect
     * @throws \Magento\Framework\Exception\NotFoundException|\Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $collection = $this->filter->getCollection($this->collectionFactory->create());

        /** @var CatalogCollection $collection */
        $this->collectionResource->setProcessedByIds($collection->getAllProductIds());

        $this->messageManager->addSuccessMessage(
            __(
                'A total of %1 record(s) have been set as processed.',
                $collection->getSize()
            )
        );

        return $resultRedirect->setPath('*/*/');
    }
}
