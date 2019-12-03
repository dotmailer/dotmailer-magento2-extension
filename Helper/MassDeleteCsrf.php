<?php

namespace Dotdigitalgroup\Email\Helper;

use Magento\Framework\Controller\ResultFactory;

abstract class MassDeleteCsrf extends \Magento\Backend\App\Action
{
    /**
     * Inherited
     */
    protected $collectionResource;

    /**
     * Inherited
     */
    protected $filter;

    /**
     * Inherited
     */
    protected $collectionFactory;

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        if (!$this->getRequest()->isPost()) {
            throw new \Magento\Framework\Exception\NotFoundException(__('Page not found.'));
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collectionSize = $collection->getSize();

        foreach ($collection as $item) {
            $this->collectionResource->delete($item);
        }

        $this->messageManager->addSuccess(__('A total of %1 record(s) have been deleted.', $collectionSize));

        return $resultRedirect->setPath('*/*/');
    }
}
