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
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $dataHelper;

    /**
     * Save constructor.
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Framework\Escaper $escaper,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->dataHelper     = $data;
        $this->escaper = $escaper;
        $this->messageManager = $context->getMessageManager();
        parent::__construct($context);
    }

    /**
     * Execute method.
     *
     * @return void
     */
    public function execute()
    {
        $datafield  = $this->getRequest()->getParam('name');

        if (! empty($datafield)) {
            $response = $this->dataHelper->createDatafield(
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
