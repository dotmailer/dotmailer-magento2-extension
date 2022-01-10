<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Catalogsync extends \Magento\Backend\App\AbstractAction
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::config';

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Dotdigitalgroup\Email\Model\Sync\CatalogFactory
     */
    private $catalogFactory;

    /**
     * Catalogsync constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Sync\CatalogFactory $catalogFactory
     * @param \Magento\Backend\App\Action\Context              $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Sync\CatalogFactory $catalogFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->catalogFactory = $catalogFactory;
        $this->messageManager = $context->getMessageManager();
        parent::__construct($context);
    }

    /**
     * Refresh suppressed contacts.
     *
     * @return void
     */
    public function execute()
    {
        $result = $this->catalogFactory->create(
            ['data' => ['web' => true]]
        )->sync();

        $this->messageManager->addSuccessMessage($result['message']);

        $redirectBack = $this->_redirect->getRefererUrl();
        $this->_redirect($redirectBack);
    }
}
