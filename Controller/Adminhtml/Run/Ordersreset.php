<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Ordersreset extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\OrderFactory
     */
    protected $_orderFactory;

    /**
     * Ordersreset constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\OrderFactory $orderFactory
     * @param \Magento\Backend\App\Action\Context                $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\OrderFactory $orderFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_orderFactory = $orderFactory;
        $this->messageManager = $context->getMessageManager();
        parent::__construct($context);
    }

    /**
     * Refresh suppressed contacts.
     */
    public function execute()
    {
        $this->_orderFactory->create()
            ->resetOrders();

        $this->messageManager->addSuccess(__('Done.'));

        $redirectUrl = $this->getUrl('adminhtml/system_config/edit', ['section' => 'connector_developer_settings']);

        $this->_redirect($redirectUrl);
    }
}
