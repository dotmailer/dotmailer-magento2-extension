<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Templatesync extends \Magento\Backend\App\AbstractAction
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::config';

    /**
     * @var \Dotdigitalgroup\Email\Model\Email\TemplateFactory
     */
    private $emailTemplatesFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * Templatesync constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Email\TemplateFactory $templateFactory
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Email\TemplateFactory $templateFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->emailTemplatesFactory = $templateFactory;
        $this->messageManager = $context->getMessageManager();
        parent::__construct($context);
    }

    /**
     *
     * @return null
     */
    public function execute()
    {
        //run sync
        $result = $this->emailTemplatesFactory->create()
            ->sync();

        //add sync message
        if (isset($result['message'])) {
            $this->messageManager->addSuccessMessage($result['message']);
        }

        $redirectBack = $this->_redirect->getRefererUrl();
        $this->_redirect($redirectBack);
    }
}
