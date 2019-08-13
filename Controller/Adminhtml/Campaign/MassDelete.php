<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Campaign;

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
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::campaign';

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory
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
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Campaign
     */
    protected $collectionResource;

    /**
     * MassDelete constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Campaign $collectionResource
     * @param \Magento\Backend\App\Action\Context $context
     * @param Filter $filter
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory $collectionFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Campaign $collectionResource,
        \Magento\Backend\App\Action\Context $context,
        Filter $filter,
        \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory $collectionFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->collectionResource = $collectionResource;
        parent::__construct($context);
    }
}
