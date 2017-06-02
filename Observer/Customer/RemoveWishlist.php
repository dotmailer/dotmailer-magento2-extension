<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

/**
 * Wishlist single delete when item is removed from wishlist.
 */
class RemoveWishlist implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\WishlistFactory
     */
    public $wishlistFactory;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    public $customerFactory;
    /**
     * @var
     */
    public $importerFactory;

    /**
     * RemoveWishlist constructor.
     *
     * @param \Magento\Customer\Model\CustomerFactory      $customerFactory
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Dotdigitalgroup\Email\Model\WishlistFactory $wishlistFactory
     * @param \Dotdigitalgroup\Email\Helper\Data           $data
     * @param \Magento\Store\Model\StoreManagerInterface   $storeManagerInterface
     */
    public function __construct(
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Model\WishlistFactory $wishlistFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->importerFactory = $importerFactory;
        $this->customerFactory = $customerFactory;
        $this->wishlistFactory = $wishlistFactory;
        $this->helper          = $data;
        $this->storeManager    = $storeManagerInterface;
    }

    /**
     * If it's configured to capture on shipment - do this.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $object = $observer->getEvent()->getDataObject();
        $customer = $this->customerFactory->create();
        $customer = $customer->getResource()->load($customer, $object->getCustomerId());
        $website = $this->storeManager->getStore($customer->getStoreId())
            ->getWebsite();

        //sync enabled
        $syncEnabled = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_WISHLIST_ENABLED,
            $website->getId()
        );
        if ($this->helper->isEnabled($website->getId()) && $syncEnabled) {
            //Remove wishlist
            try {
                $item = $this->wishlistFactory->create()
                    ->getWishlist($object->getWishlistId());
                if ($item->getId()) {
                    //register in queue with importer
                    $this->importerFactory->create()->registerQueue(
                        \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_WISHLIST,
                        [$item->getId()],
                        \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE_DELETE,
                        $website->getId()
                    );
                    $item->getResource()->delete($item);
                }
            } catch (\Exception $e) {
                $this->helper->debug((string)$e, []);
            }
        }
    }
}
