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
    private $helper;
    
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;
    
    /**
     * @var \Dotdigitalgroup\Email\Model\WishlistFactory
     */
    private $wishlistFactory;
    
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    private $customerFactory;
    
    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    private $importerFactory;
    
    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer
     */
    private $customerResource;
    
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist
     */
    private $wishlistResource;

    /**
     * RemoveWishlist constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist $wishlistResource
     * @param \Magento\Customer\Model\ResourceModel\Customer $customerResource
     * @param \Magento\Customer\Model\CustomerFactory      $customerFactory
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Dotdigitalgroup\Email\Model\WishlistFactory $wishlistFactory
     * @param \Dotdigitalgroup\Email\Helper\Data           $data
     * @param \Magento\Store\Model\StoreManagerInterface   $storeManagerInterface
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist $wishlistResource,
        \Magento\Customer\Model\ResourceModel\Customer $customerResource,
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
        $this->customerResource = $customerResource;
        $this->wishlistResource = $wishlistResource;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return null
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $object = $observer->getEvent()->getDataObject();
        $customer = $this->customerFactory->create();
        $this->customerResource->load($customer, $object->getCustomerId());
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
                if (($item instanceof \Magento\Framework\DataObject) && $item->getId()) {
                    //register in queue with importer
                    $this->importerFactory->create()->registerQueue(
                        \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_WISHLIST,
                        [$item->getWishlistId()],
                        \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE_DELETE,
                        $website->getId()
                    );
                    $this->wishlistResource->delete($item);
                }
            } catch (\Exception $e) {
                $this->helper->debug((string)$e, []);
            }
        }
    }
}
