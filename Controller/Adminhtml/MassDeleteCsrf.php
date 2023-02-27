<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;

abstract class MassDeleteCsrf extends Action implements HttpPostActionInterface
{
    /**
     * Inherited
     */
    protected $collectionResource;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * Inherited
     */
    protected $collectionFactory;

    /**
     * @return Redirect
     * @throws \Magento\Framework\Exception\NotFoundException|\Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collectionSize = $collection->getSize();

        foreach ($collection as $item) {
            $this->collectionResource->delete($item);
        }

        $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been deleted.', $collectionSize));

        return $resultRedirect->setPath('*/*/');
    }
}
