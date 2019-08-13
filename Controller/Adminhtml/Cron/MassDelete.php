<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Cron;

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
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::cron';

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Cron\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Cron\Model\ResourceModel\Schedule
     */
    protected $collectionResource;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * MassDelete constructor.
     *
     * @param \Magento\Cron\Model\ResourceModel\Schedule $collectionResource
     * @param \Magento\Backend\App\Action\Context $context
     * @param Filter $filter
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Cron\CollectionFactory $collectionFactory
     */
    public function __construct(
        \Magento\Cron\Model\ResourceModel\Schedule $collectionResource,
        \Magento\Backend\App\Action\Context $context,
        Filter $filter,
        \Dotdigitalgroup\Email\Model\ResourceModel\Cron\CollectionFactory $collectionFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->collectionResource = $collectionResource;
        parent::__construct($context);
    }
}
