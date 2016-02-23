<?php


namespace Dotdigitalgroup\Email\Observer\Adminhtml;

use Magento\Framework\Event\ObserverInterface;

class ResetContactImport implements ObserverInterface
{

    protected $_helper;
    protected $_context;
    protected $_request;
    protected $_storeManager;
    protected $messageManager;
    protected $_contactFactory;
    protected $_contactResourceFactory;

    /**
     * ResetContactImport constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Resource\ContactFactory $contactResourceFactory
     * @param \Dotdigitalgroup\Email\Model\ContactFactory          $contactFactory
     * @param \Dotdigitalgroup\Email\Helper\Data                   $data
     * @param \Magento\Backend\App\Action\Context                  $context
     * @param \Magento\Store\Model\StoreManagerInterface           $storeManagerInterface
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Resource\ContactFactory $contactResourceFactory,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->_contactFactory         = $contactFactory;
        $this->_contactResourceFactory = $contactResourceFactory;
        $this->_helper                 = $data;
        $this->_context                = $context;
        $this->_contactFactory         = $contactFactory;
        $this->_request                = $context->getRequest();
        $this->_storeManager           = $storeManagerInterface;
        $this->messageManager          = $context->getMessageManager();
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $contactModel = $this->_contactResourceFactory->create();
        $numImported  = $this->_contactFactory->create()
            ->getNumberOfImportedContacs();

        $updated = $contactModel->resetAllContacts();

        $this->_helper->log('-- Imported contacts: ' . $numImported
            . ' reseted :  ' . $updated . ' --');

        return $this;
    }
}
