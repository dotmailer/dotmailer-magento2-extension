<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Datafield;

class Save extends \Magento\Backend\App\AbstractAction
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::automation';

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Dotdigitalgroup\Email\Model\Apiconnector\DataField
     */
    private $datafieldHandler;

    /**
     * Save constructor.
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\DataField $datafieldHandler
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Apiconnector\DataField $datafieldHandler,
        \Magento\Framework\Escaper $escaper,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->datafieldHandler = $datafieldHandler;
        $this->escaper = $escaper;
        $this->messageManager = $context->getMessageManager();
        parent::__construct($context);
    }

    /**
     * Execute method.
     *
     * @return null|void
     */
    public function execute()
    {
        $datafield  = $this->getRequest()->getParam('name');

        if (!empty($datafield)) {
            if (!$this->datafieldHandler->hasValidLength($datafield)) {
                $this->messageManager->addErrorMessage(__('Please limit Data Field Name to 20 characters.'));
                return;
            }
            $response = $this->datafieldHandler->createDatafield(
                (int) $this->getRequest()->getParam('website_id'),
                $datafield,
                $this->getRequest()->getParam('type'),
                $this->getRequest()->getParam('visibility'),
                $this->getRequest()->getParam('default')
            );

            if (isset($response->message)) {
                $this->messageManager->addErrorMessage($response->message);
            } else {
                $this->messageManager->addSuccessMessage(__('Data field successfully created.'));
            }
        }
    }
}
