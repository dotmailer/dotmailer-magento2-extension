<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

/**
 * Wishlist delete.
 */
class RemoveWishlist implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customer;

    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    private $importerFactory;

    /**
     * RemoveWishlist constructor.
     *
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customer
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     */
    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customer,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->importerFactory = $importerFactory;
        $this->customer        = $customer;
        $this->helper          = $data;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            /** @var \Magento\Wishlist\Model\Wishlist $wishlist */
            $wishlist = $observer->getEvent()->getDataObject();
            $customer = $this->customer->getById($wishlist->getCustomerId());
            $isEnabled = $this->helper->isEnabled($customer->getWebsiteId());
            $syncEnabled = $this->helper->getWebsiteConfig(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_WISHLIST_ENABLED,
                $customer->getWebsiteId()
            );

            //create a queue item to remove single wishlist
            if ($isEnabled && $syncEnabled && $wishlist->getId()) {
                //register in queue with importer
                $this->importerFactory->create()->registerQueue(
                    \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_WISHLIST,
                    [$wishlist->getId()],
                    \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE_DELETE,
                    $customer->getWebsiteId()
                );
            }
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }
    }
}
