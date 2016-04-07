<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Addressbook;

use DotMailer\Api\DataTypes\ApiAddressBook;

class Save extends \Magento\Backend\App\AbstractAction
{

    protected $messageManager;
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
        $this->_helperData    = $data;
        $this->messageManager = $context->getMessageManager();
        parent::__construct($context);

    }

    public function execute()
    {
        $addressBookName = $this->getRequest()->getParam('name');
        $client          = $this->_helperData->getWebsiteApiClient(
            $this->getRequest()->getParam('website', 0)
        );

        if (strlen($addressBookName)) {

            $addressBook             = new ApiAddressBook();
            $addressBook->name       = $addressBookName;
            $addressBook->visibility = $this->getRequest()->getParam(
                'visibility', 'Public'
            );

            $client->PostAddressBooks($addressBook);
        }
    }

}