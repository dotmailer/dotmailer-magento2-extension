<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Wishlistsreset extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    public $messageManager;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\WishlistFactory
     */
    public $wishlistFactory;

    /**
     * Wishlistsreset constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\WishlistFactory $wishlistFactory
     * @param \Magento\Backend\App\Action\Context                        $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\WishlistFactory $wishlistFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->wishlistFactory = $wishlistFactory;
        $this->messageManager  = $context->getMessageManager();
        parent::__construct($context);
    }

    /**
     * Refresh suppressed contacts.
     */
    public function execute()
    {
        $this->wishlistFactory->create()
            ->resetWishlists();

        $this->messageManager->addSuccessMessage(__('Done.'));

        $redirectUrl = $this->getUrl('adminhtml/system_config/edit', ['section' => 'connector_developer_settings']);

        $this->_redirect($redirectUrl);
    }
}
