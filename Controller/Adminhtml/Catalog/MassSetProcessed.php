<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Catalog;

use Dotdigitalgroup\Email\Model\ResourceModel\Catalog;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog\Collection as CatalogCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;

class MassSetProcessed extends \Magento\Backend\App\Action
{
    /**
     * Authorization level
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::catalog';

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var Catalog
     */
    private $collectionResource;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * MassSetProcessed constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param Filter $filter
     * @param Catalog $collectionResource
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Filter $filter,
        Catalog $collectionResource,
        CollectionFactory $collectionFactory
    ) {
        $this->filter = $filter;
        $this->collectionResource = $collectionResource;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }

    /**
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

        /** @var CatalogCollection $collection */
        $this->collectionResource->setProcessedByIds($collection->getAllProductIds());

        $this->messageManager->addSuccessMessage(
            __(
                'A total of %1 record(s) have been set as processed.',
                $collectionSize
            )
        );

        return $resultRedirect->setPath('*/*/');
    }

    /**
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
