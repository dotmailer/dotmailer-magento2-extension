<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Abandoned;

use Dotdigitalgroup\Email\Helper\MassDeleteCsrf;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;

class MassDelete extends MassDeleteCsrf
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
    protected $collectionResource;

    /**
     * @var
     */
    protected $abandonedCollection;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * MassDelete constructor.
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned $collectionResource
     * @param \Magento\Backend\App\Action\Context $context
     * @param Filter $filter
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned\CollectionFactory $abandonedCollection
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned $collectionResource,
        \Magento\Backend\App\Action\Context $context,
        Filter $filter,
        \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned\CollectionFactory $abandonedCollection
    ) {
        $this->filter = $filter;
        $this->abandonedCollection = $abandonedCollection->create();
        $this->collectionResource = $collectionResource;
        parent::__construct($context);
    }
}
