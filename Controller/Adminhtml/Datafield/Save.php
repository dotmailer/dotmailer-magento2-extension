<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Datafield;

class Save extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_dataHelper;

    /**
     * Save constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_dataHelper = $data;
        $this->messageManager = $context->getMessageManager();
        parent::__construct($context);
    }

    /**
     * Execute method.
     */
    public function execute()
    {
        $datafield = $this->getRequest()->getParam('name');
        $type = $this->getRequest()->getParam('type');
        $default = $this->getRequest()->getParam('default');
        $visibility = $this->getRequest()->getParam('visibility');

        $website = $this->getRequest()->getParam('website', 0);

        $client = $this->_dataHelper->getWebsiteApiClient($website);

        if (strlen($datafield)) {
            $response = $client->postDataFields($datafield, $type, $visibility, $default);
            if (isset($response->message)) {
                $this->messageManager->addError($response->message);
            } else {
                $this->messageManager->addSuccess('Datafield : ' . $datafield . ' created.');
            }
        }
    }
}
