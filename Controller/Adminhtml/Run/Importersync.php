<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Importersync extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    protected $_importerFactory;

    /**
     * Importersync constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_importerFactory = $importerFactory;
        $this->messageManager = $context->getMessageManager();

        parent::__construct($context);
    }

    /**
     * Refresh suppressed contacts.
     */
    public function execute()
    {
        $result = $this->_importerFactory->create()->processQueue();

        $this->messageManager->addSuccess($result['message']);

        $redirectBack = $this->_redirect->getRefererUrl();

        $this->_redirect($redirectBack);
    }
}
