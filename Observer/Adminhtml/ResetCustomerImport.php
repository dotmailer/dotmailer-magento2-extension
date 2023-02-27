<?php

namespace Dotdigitalgroup\Email\Observer\Adminhtml;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;

/**
 * Reset the contact import after changing the mapping.
 */
class ResetCustomerImport implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ContactCollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var ContactResource
     */
    private $contactResource;

    /**
     * ResetCustomerImport constructor.
     *
     * @param ContactResource $contactResource
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param Data $data
     */
    public function __construct(
        ContactResource $contactResource,
        ContactCollectionFactory $contactCollectionFactory,
        Data $data
    ) {
        $this->contactResource = $contactResource;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->helper = $data;
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
        $numImported = $this->contactCollectionFactory->create()
            ->getNumberOfImportedCustomers();

        $updated = $this->contactResource->resetAllCustomers();

        $this->helper->log(
            '-- Imported customers: ' . $numImported
            . ' reset :  ' . $updated . ' --'
        );

        return $this;
    }
}
