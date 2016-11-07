<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Catalogsync extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    public $messageManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\Sync\CatalogFactory
     */
    public $catalogFactory;

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
     */
    public function execute()
    {
        $result = $this->catalogFactory->create()
            ->sync();

        $this->messageManager->addSuccessMessage($result['message']);

        $redirectUrl = $this->getUrl('adminhtml/system_config/edit', ['section' => 'connector_developer_settings']);

        $this->_redirect($redirectUrl);
    }
}
