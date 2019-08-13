<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Order;

use Dotdigitalgroup\Email\Controller\Adminhtml\Order as OrderController;
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
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::order';

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var object
     */
    protected $messageManager;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Order
     */
    protected $collectionResource;

    /**
     * MassDelete constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Order $collectionResource
     * @param \Magento\Backend\App\Action\Context $context
     * @param Filter $filter
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Order $collectionResource,
        \Magento\Backend\App\Action\Context $context,
        Filter $filter,
        \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory $collectionFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->collectionResource = $collectionResource;
        parent::__construct($context);
    }
}
