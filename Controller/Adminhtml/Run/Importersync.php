<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Importersync extends \Magento\Backend\App\AbstractAction
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
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    private $importerFactory;

    /**
     * Importersync constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Sync\ImporterFactory $importerFactory
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Sync\ImporterFactory $importerFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->importerFactory = $importerFactory;
        $this->messageManager  = $context->getMessageManager();

        parent::__construct($context);
    }

    /**
     * Refresh suppressed contacts.
     *
     * @return null
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
