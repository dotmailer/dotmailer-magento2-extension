<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Wishlist;

use Dotdigitalgroup\Email\Helper\MassDeleteCsrf;
use Magento\Ui\Component\MassAction\Filter;

class MassDelete extends MassDeleteCsrf
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::wishlist';

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory
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
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist
     */
    protected $collectionResource;

    /**
     * MassDelete constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist $collectionResource
     * @param \Magento\Backend\App\Action\Context $context
     * @param Filter $filter
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory $collectionFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist $collectionResource,
        \Magento\Backend\App\Action\Context $context,
        Filter $filter,
        \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory $collectionFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->collectionResource = $collectionResource;
        parent::__construct($context);
    }
}
