<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;

class Wishlistsync extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::config';

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Dotdigitalgroup\Email\Model\Sync\WishlistFactory
     */
    private $wishlistFactory;

    /**
     * Wishlistsync constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Sync\WishlistFactory $wishlistFactory
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Sync\WishlistFactory $wishlistFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->wishlistFactory = $wishlistFactory;
        $this->messageManager  = $context->getMessageManager();
        parent::__construct($context);
    }

    /**
     * Refresh suppressed contacts.
     *
     * @return void
     */
    public function execute()
    {
        $result = $this->wishlistFactory->create()
            ->sync();

        $this->messageManager->addSuccessMessage($result['message']);

        $redirectBack = $this->_redirect->getRefererUrl();
        $this->_redirect($redirectBack);
    }
}
