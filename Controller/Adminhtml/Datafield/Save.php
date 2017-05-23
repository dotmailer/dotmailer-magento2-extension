<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Datafield;

/**
 * Class Save
 * @package Dotdigitalgroup\Email\Controller\Adminhtml\Datafield
 */
class Save extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    public $messageManager;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $dataHelper;

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
        $this->dataHelper     = $data;
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

        $client = $this->dataHelper->getWebsiteApiClient($website);

        if (! empty($datafield)) {
            $response = $client->postDataFields($datafield, $type, $visibility, $default);
            if (isset($response->message)) {
                $this->messageManager->addErrorMessage($response->message);
            } else {
                $this->messageManager->addSuccessMessage('Datafield : ' . $datafield . ' created.');
            }
        }
    }


    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dotdigitalgroup_Email::automation');
    }
}
