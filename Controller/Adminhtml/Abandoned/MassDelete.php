<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Abandoned;

use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;

class MassDelete extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::abandoned';

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned
     */
    public $abandonedResource;

    /**
     * @var
     */
    public $abandonedCollection;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Automation
     */
    private $automationResource;

    /**
     * MassDelete constructor.
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned $abandonedResource
     * @param \Magento\Backend\App\Action\Context $context
     * @param Filter $filter
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned\CollectionFactory $abandonedCollection
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned $abandonedResource,
        \Magento\Backend\App\Action\Context $context,
        Filter $filter,
        \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned\CollectionFactory $abandonedCollection
    ) {
        $this->filter = $filter;
        $this->abandonedCollection = $abandonedCollection->create();
        $this->abandonedResource = $abandonedResource;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->abandonedCollection);
        $collectionSize = $collection->getSize();

        foreach ($collection as $item) {
            $this->abandonedResource->delete($item);
        }

        $this->messageManager->addSuccess(__('A total of %1 record(s) have been deleted.', $collectionSize));

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        return $resultRedirect->setPath('*/*/');
    }
}
