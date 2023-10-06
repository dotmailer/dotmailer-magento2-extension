<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Addressbook;

use Dotdigitalgroup\Email\Helper\Data;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;

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
     * @var RequestInterface
     */
    private $request;

    /**
     * Save constructor.
     *
     * @param Data $data
     * @param Context $context
     */
    public function __construct(
        Data $data,
        Context $context
    ) {
        $this->helperData = $data;
        $this->messageManager = $context->getMessageManager();
        $this->request = $context->getRequest();

        parent::__construct($context);
    }

    /**
     * Execute method.
     *
     * @return void
     * @throws \Exception
     */
    public function execute()
    {
        $addressBookName = $this->request->getParam('name');
        $visibility = $this->request->getParam('visibility');
        $website = (int) $this->request->getParam('website', 0);

        if ($this->helperData->isEnabled($website)) {
            $client = $this->helperData->getWebsiteApiClient($website);
            if (! empty($addressBookName)) {
                $response = $client->postAddressBooks($addressBookName, $visibility);
                if (isset($response->message)) {
                    $this->messageManager->addErrorMessage($response->message);
                } else {
                    $this->messageManager->addSuccessMessage('List successfully created.');
                }
            }
        }
    }
}
