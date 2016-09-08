<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

class RemoveWishlist implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\WishlistFactory
     */
    protected $_wishlistFactory;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;
    /**
     * @var
     */
    protected $_importerFactory;

    /**
     * RemoveWishlist constructor.
     *
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Dotdigitalgroup\Email\Model\WishlistFactory $wishlistFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Model\WishlistFactory $wishlistFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->_importerFactory = $importerFactory;
        $this->_customerFactory = $customerFactory;
        $this->_wishlistFactory = $wishlistFactory;
        $this->_helper = $data;
        $this->_storeManager = $storeManagerInterface;
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
        $customer = $this->_customerFactory->create()
            ->load($object->getCustomerId());
        $website = $this->_storeManager->getStore($customer->getStoreId())
            ->getWebsite();

        //sync enabled
        $syncEnabled = $this->_helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_WISHLIST_ENABLED,
            $website->getId()
        );
        if ($this->_helper->isEnabled($website->getId()) && $syncEnabled) {
            //Remove wishlist
            try {
                $item = $this->_wishlistFactory->create()
                    ->getWishlist($object->getWishlistId());
                if (($item instanceof \Magento\Framework\DataObject) && $item->getId()) {
                    //register in queue with importer
                    $this->_importerFactory->create()->registerQueue(
                        \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_WISHLIST,
                        [$item->getId()],
                        \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE_DELETE,
                        $website->getId()
                    );
                    $item->delete();
                }
            } catch (\Exception $e) {
                $this->_helper->debug((string)$e, []);
            }
        }
    }
}
