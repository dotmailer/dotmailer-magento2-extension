<?php

namespace Dotdigitalgroup\Email\Observer\Adminhtml;

class ResetContactImport implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    protected $_contactFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory
     */
    protected $_contactResourceFactory;

    /**
     * ResetContactImport constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory $contactResourceFactory
     * @param \Dotdigitalgroup\Email\Model\ContactFactory          $contactFactory
     * @param \Dotdigitalgroup\Email\Helper\Data                   $data
     * @param \Magento\Backend\App\Action\Context                  $context
     * @param \Magento\Store\Model\StoreManagerInterface           $storeManagerInterface
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory $contactResourceFactory,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_contactFactory = $contactFactory;
        $this->_contactResourceFactory = $contactResourceFactory;
        $this->_helper = $data;
        $this->_request = $context->getRequest();
        $this->messageManager = $context->getMessageManager();
    }

    /**
     * Execute method.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     * @codingStandardsIgnoreStart
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        //@codingStandardsIgnoreEnd
        $contactModel = $this->_contactResourceFactory->create();
        $numImported = $this->_contactFactory->create()
            ->getNumberOfImportedContacs();

        $updated = $contactModel->resetAllContacts();

        $this->_helper->log('-- Imported contacts: ' . $numImported
            . ' reseted :  ' . $updated . ' --');

        return $this;
    }
}
