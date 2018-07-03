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
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;
    
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
     * RemoveWishlist constructor.
     * @param \Magento\Customer\Model\ResourceModel\Customer $customerResource
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        \Magento\Customer\Model\ResourceModel\Customer $customerResource,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->importerFactory = $importerFactory;
        $this->customerFactory = $customerFactory;
        $this->helper          = $data;
        $this->storeManager    = $storeManagerInterface;
        $this->customerResource = $customerResource;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $wishlist = $observer->getEvent()->getDataObject();
        $customer = $this->customerFactory->create();
        $this->customerResource->load($customer, $wishlist->getCustomerId());
        $websiteId = $this->storeManager->getStore($customer->getStoreId())
            ->getWebsiteId();
        $isEnabled = $this->helper->isEnabled($websiteId);
        $syncEnabled = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_WISHLIST_ENABLED,
            $websiteId
        );

        try {
            //create a queue item to remote single wishlist
            if ($isEnabled && $syncEnabled && $wishlist->getId()) {
                //register in queue with importer
                $this->importerFactory->create()->registerQueue(
                    \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_WISHLIST,
                    [$wishlist->getId()],
                    \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE_DELETE,
                    $websiteId
                );
            }
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }
    }
}
