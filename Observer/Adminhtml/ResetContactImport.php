<?php

namespace Dotdigitalgroup\Email\Observer\Adminhtml;

/**
 * Reset the contact import after changing the mapping.
 */
class ResetContactImport implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    private $contactFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact
     */
    private $contactResource;

    /**
     * ResetContactImport constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact        $contactResource
     * @param \Dotdigitalgroup\Email\Model\ContactFactory               $contactFactory
     * @param \Dotdigitalgroup\Email\Helper\Data                        $data
     * @param \Magento\Backend\App\Action\Context                       $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->contactFactory         = $contactFactory;
        $this->contactResource        = $contactResource;
        $this->helper                 = $data;
        $this->messageManager         = $context->getMessageManager();
    }

    /**
     * Execute method.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $numImported = $this->contactFactory->create()
            ->getNumberOfImportedContacts();

        $updated = $this->contactResource->resetAllContacts();

        $this->helper->log(
            '-- Imported contacts: ' . $numImported
            . ' reset :  ' . $updated . ' --'
        );

        return $this;
    }
}
