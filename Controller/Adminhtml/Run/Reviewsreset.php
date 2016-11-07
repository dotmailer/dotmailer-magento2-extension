<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Reviewsreset extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    public $messageManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\ReviewFactory
     */
    public $reviewFactory;

    /**
     * Reviewsreset constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\ReviewFactory $reviewFactory
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\ReviewFactory $reviewFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->reviewFactory  = $reviewFactory;
        $this->messageManager = $context->getMessageManager();
        parent::__construct($context);
    }

    /**
     * Refresh suppressed contacts.
     */
    public function execute()
    {
        $this->reviewFactory->create()
            ->resetReviews();

        $this->messageManager->addSuccessMessage(__('Done.'));

        $redirectUrl = $this->getUrl('adminhtml/system_config/edit', ['section' => 'connector_developer_settings']);

        $this->_redirect($redirectUrl);
    }
}
