<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Addressbook;

use Dotdigitalgroup\Email\Helper\Data;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

class Save extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::config';

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helperData;

    /**
     * Save constructor.
     * @param Data $data
     * @param Action\Context $context
     */
    public function __construct(
        Data $data,
        Action\Context $context
    ) {
        $this->helperData = $data;
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
        $addressBookName = $this->getRequest()->getParam('name');
        $visibility = $this->getRequest()->getParam('visibility');
        $website = (int) $this->getRequest()->getParam('website', 0);

        if ($this->helperData->isEnabled($website)) {
            $client = $this->helperData->getWebsiteApiClient($website);
            if (! empty($addressBookName)) {
                $response = $client->postAddressBooks($addressBookName, $visibility);
                if (isset($response->message)) {
                    $this->messageManager->addErrorMessage($response->message);
                } else {
                    $this->messageManager->addSuccessMessage('Address book successfully created.');
                }
            }
        }
    }
}
