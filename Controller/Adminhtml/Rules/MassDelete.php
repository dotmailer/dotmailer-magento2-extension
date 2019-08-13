<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

use Dotdigitalgroup\Email\Helper\MassDeleteCsrf;
use Magento\Ui\Component\MassAction\Filter;


class MassDelete extends MassDeleteCsrf
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::exclusion_rules';

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Rules\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Rules
     */
    protected $collectionResource;

    /**
     * MassDelete constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Rules $collectionResource
     * @param \Magento\Backend\App\Action\Context $context
     * @param Filter $filter
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Rules\CollectionFactory $collectionFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Rules $collectionResource,
        \Magento\Backend\App\Action\Context $context,
        Filter $filter,
        \Dotdigitalgroup\Email\Model\ResourceModel\Rules\CollectionFactory $collectionFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->collectionResource = $collectionResource;
        parent::__construct($context);
    }
}
