<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Catalogreset extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    public $messageManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory
     */
    public $catalogFactory;

    /**
     * Catalogreset constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory $catalogFactory
     * @param \Magento\Backend\App\Action\Context                       $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory $catalogFactory,
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
        $this->catalogFactory->create()
            ->resetCatalog();

        $this->messageManager->addSuccessMessage(__('Done.'));

        $redirectUrl = $this->getUrl(
            'adminhtml/system_config/edit',
            ['section' => 'connector_developer_settings']
        );

        $this->_redirect($redirectUrl);
    }
}
