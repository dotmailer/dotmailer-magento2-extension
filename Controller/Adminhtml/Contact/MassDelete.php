<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Contact;

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
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::contact';

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory
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
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact
     */
    protected $collectionResource;

    /**
     * MassDelete constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact $collectionResource
     * @param \Magento\Backend\App\Action\Context $context
     * @param Filter $filter
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory $collectionFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact $collectionResource,
        \Magento\Backend\App\Action\Context $context,
        Filter $filter,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory $collectionFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->collectionResource = $collectionResource;
        parent::__construct($context);
    }
}
