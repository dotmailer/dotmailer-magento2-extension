<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Catalogsync extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\Sync\CatalogFactory
     */
    protected $_catalogFactory;

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
        $this->_catalogFactory = $catalogFactory;
        $this->messageManager = $context->getMessageManager();
        parent::__construct($context);
    }

    /**
     * Refresh suppressed contacts.
     */
    public function execute()
    {
        $result = $this->_catalogFactory->create()
            ->sync();

        $this->messageManager->addSuccess($result['message']);

        $redirectUrl = $this->getUrl('adminhtml/system_config/edit', ['section' => 'connector_developer_settings']);

        $this->_redirect($redirectUrl);
    }
}
