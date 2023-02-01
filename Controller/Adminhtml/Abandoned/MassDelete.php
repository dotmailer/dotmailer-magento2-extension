<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Abandoned;

use Dotdigitalgroup\Email\Controller\Adminhtml\MassDeleteCsrf;
use \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned as AbandonedResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Abandoned\CollectionFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;

class MassDelete extends MassDeleteCsrf
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::abandoned';

    /**
     * @var AbandonedResource
     */
    protected $collectionResource;

    /**
     * @var CollectionFactory
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
     * MassDelete constructor.
     * @param AbandonedResource $collectionResource
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        AbandonedResource $collectionResource,
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->collectionResource = $collectionResource;
        parent::__construct($context);
    }
}
