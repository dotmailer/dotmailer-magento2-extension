<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

use Dotdigitalgroup\Email\Model\Sync\ImporterFactory;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;

class Importersync extends Action implements HttpGetActionInterface
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
     * @var ImporterFactory
     */
    private $importerFactory;

    /**
     * Importersync constructor.
     *
     * @param ImporterFactory $importerFactory
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        ImporterFactory $importerFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->importerFactory = $importerFactory;
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
        $this->importerFactory->create()
            ->sync();

        $this->messageManager->addSuccessMessage('Done.');

        $redirectBack = $this->_redirect->getRefererUrl();

        $this->_redirect($redirectBack);
    }
}
