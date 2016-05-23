<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Addressbook;

class Save extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helperData;

    /**
     * Save constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data  $data
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_helperData = $data;
        $this->messageManager = $context->getMessageManager();
        parent::__construct($context);
    }

    /**
     * Execute method.
     */
    public function execute()
    {
        $addressBookName = $this->getRequest()->getParam('name');
        $visibility = $this->getRequest()->getParam('visibility');
        $website = $this->getRequest()->getParam('website', 0);

        $client = $this->_helperData->getWebsiteApiClient($website);
        if (strlen($addressBookName)) {
            $response = $client->postAddressBooks($addressBookName, $visibility);
            if (isset($response->message)) {
                $this->messageManager->addError($response->message);
            } else {
                $this->messageManager->addSuccess('Address book : '.$addressBookName.' created.');
            }
        }
    }
}
