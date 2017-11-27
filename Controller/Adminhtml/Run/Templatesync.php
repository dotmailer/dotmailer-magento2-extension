<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Templatesync extends \Magento\Backend\App\AbstractAction
{
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
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $result = $this->emailTemplatesFactory->create()
            ->sync();

        if (isset($result['message'])) {
            $this->messageManager->addSuccessMessage($result['message']);
        }

        $this->_redirect($this->_redirect->getRefererUrl());
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dotdigitalgroup_Email::config');
    }
}
