<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Importer;

use Dotdigitalgroup\Email\Controller\Adminhtml\Importer as ImporterController;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;

class MassDelete extends ImporterController
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Importer\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var object
     */
    protected $messageManager;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * MassDelete constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param Filter $filter
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Importer\CollectionFactory $collectionFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Filter $filter,
        \Dotdigitalgroup\Email\Model\ResourceModel\Importer\CollectionFactory $collectionFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collectionSize = $collection->getSize();

        foreach ($collection as $item) {
            $item->getResource()->delete($item);
        }

        $this->messageManager->addSuccess(__('A total of %1 record(s) have been deleted.', $collectionSize));

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        return $resultRedirect->setPath('*/*/');
    }


    /**
     * Check the permission to run it.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dotdigitalgroup_Email::importer');
    }
}
