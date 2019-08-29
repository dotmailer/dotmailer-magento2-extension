<?php

namespace Dotdigitalgroup\Email\Model\Sync;

use Dotdigitalgroup\Email\Model\Importer;

/**
 * Sync Wishlists.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Wishlist implements SyncInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var array
     */
    private $wishlists;

    /**
     * @var array
     */
    private $wishlistIds = [];

    /**
     * @var mixed
     */
    private $start;

    /**
     * @var int
     */
    private $countWishlists = 0;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    private $customerFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    private $importerFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\WishlistFactory
     */
    private $wishlist;

    /**
     * @var \Dotdigitalgroup\Email\Model\Customer\WishlistFactory
     */
    private $wishlistFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\Customer\Wishlist\ItemFactory
     */
    private $itemFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory
     */
    private $wishlistCollection;

    /**
     * @var \Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory
     */
    private $itemCollection;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $datetime;

    /**
     * Wishlist constructor.
     *
     * @param \Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory $itemCollection
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory $wishlistCollection
     * @param \Dotdigitalgroup\Email\Model\Customer\Wishlist\ItemFactory $itemFactory
     * @param \Dotdigitalgroup\Email\Model\Customer\WishlistFactory $wishlistFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\WishlistFactory $wishlist
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $datetime
     */
    public function __construct(
        \Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory $itemCollection,
        \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory $wishlistCollection,
        \Dotdigitalgroup\Email\Model\Customer\Wishlist\ItemFactory $itemFactory,
        \Dotdigitalgroup\Email\Model\Customer\WishlistFactory $wishlistFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\WishlistFactory $wishlist,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime
    ) {
        $this->itemCollection     = $itemCollection;
        $this->wishlistCollection = $wishlistCollection;
        $this->itemFactory        = $itemFactory;
        $this->wishlistFactory    = $wishlistFactory;
        $this->wishlist           = $wishlist;
        $this->importerFactory    = $importerFactory;
        $this->customerFactory    = $customerFactory;
        $this->helper             = $helper;
        $this->datetime           = $datetime;
    }

    /**
     * Sync Wishlists.
     *
     * @param \DateTime|null $from
     * @return array
     */
    public function sync(\DateTime $from = null)
    {
        $response = ['success' => true, 'message' => 'Done.'];
        $websites = $this->helper->getWebsites();
        foreach ($websites as $website) {
            $wishlistEnabled = $this->helper->getWebsiteConfig(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_WISHLIST_ENABLED,
                $website
            );
            $apiEnabled      = $this->helper->isEnabled($website);
            $storeIds        = $website->getStoreIds();

            if ($wishlistEnabled && $apiEnabled && ! empty($storeIds)) {
                //using bulk api
                $this->start = microtime(true);
                $this->exportWishlistForWebsite($website);
                //send wishlist as transactional data
                if (isset($this->wishlists[$website->getId()])) {
                    $websiteWishlists = $this->wishlists[$website->getId()];
                    //register in queue with importer
                    $this->importerFactory->create()
                                          ->registerQueue(
                                              Importer::IMPORT_TYPE_WISHLIST,
                                              $websiteWishlists,
                                              Importer::MODE_BULK,
                                              $website->getId()
                                          );
                    //mark connector wishlist as  imported
                    $this->setImported($this->wishlistIds);
                }
                $message = '----------- Wishlist bulk sync ----------- : ' . gmdate('H:i:s', microtime(true) - $this->start) . ', Total synced = ' . $this->countWishlists;

                if ($this->countWishlists) {
                    $this->helper->log($message);
                }

                $response['message'] = $message;
                //using single api
                $this->exportWishlistForWebsiteInSingle($website);
            }
        }

        return $response;
    }

    /**
     *
     * @param \Magento\Store\Api\Data\WebsiteInterface $website
     *
     * @return null
     */
    public function exportWishlistForWebsite(\Magento\Store\Api\Data\WebsiteInterface $website)
    {
        //reset wishlists
        $this->wishlists   = [];
        $this->wishlistIds = [];
        //sync limit
        $limit = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT,
            $website
        );
        //wishlist collection
        $emailWishlist = $this->getWishlistToImport($website, $limit);

        $this->wishlistIds = $emailWishlist->getColumnValues('wishlist_id');

        if (! empty($this->wishlistIds)) {
            $collection = $this->wishlist->create()
                                         ->getWishlistByIds($this->wishlistIds);

            foreach ($collection as $wishlist) {
                $connectorWishlist = $this->wishlistFactory->create();
                $connectorWishlist->setId($wishlist->getId())
                                  ->setUpdatedAt($wishlist->getUpdatedAt())
                                  ->setCustomerId($wishlist->getCustomerId())
                                  ->setEmail($wishlist->getEmail());

                $wishListItemCollection = $this->itemCollection->create()
                                                               ->addWishlistFilter($wishlist);

                if ($wishListItemCollection->getSize()) {
                    foreach ($wishListItemCollection as $item) {
                        try {
                            $product = $item->getProduct();
                            $wishlistItem = $this->itemFactory->create();
                            $wishlistItem->setProduct($product)
                                ->setQty($item->getQty())
                                ->setPrice($product);
                            //store for wishlists
                            $connectorWishlist->setItem($wishlistItem);
                            ++$this->countWishlists;
                        } catch (\Exception $e) {
                            //Product does not exist. Continue to next item
                            continue;
                        }
                    }
                    //set wishlists for later use
                    $this->wishlists[$website->getId()][] = $connectorWishlist->expose();
                }
            }
        }
    }

    /**
     *
     * @param \Magento\Store\Api\Data\WebsiteInterface $website
     * @param int $limit
     *
     * @return \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\Collection
     */
    public function getWishlistToImport(\Magento\Store\Api\Data\WebsiteInterface $website, $limit = 100)
    {
        return $this->wishlistCollection->create()
                                        ->getWishlistToImportByWebsite($website, $limit);
    }

    /**
     * Export single wishlist for website.
     *
     * @param \Magento\Store\Api\Data\WebsiteInterface $website
     *
     * @return null
     */
    public function exportWishlistForWebsiteInSingle(\Magento\Store\Api\Data\WebsiteInterface $website)
    {
        //transactional data limit
        $limit             = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT,
            $website
        );
        $collection        = $this->getModifiedWishlistToImport(
            $website,
            $limit
        );
        $this->wishlistIds = [];
        //email_wishlist wishlist ids
        $wishlistIds = $collection->getColumnValues('wishlist_id');

        $wishlistCollection = $this->wishlist->create()
                                             ->getWishlistByIds($wishlistIds);

        foreach ($wishlistCollection as $wishlist) {
            $wishlistId    = $wishlist->getid();
            $wishlistItems = $this->itemCollection->create()
                                                  ->addWishlistFilter($wishlist);

            $connectorWishlist = $this->wishlistFactory->create();
            $connectorWishlist->setId($wishlistId)
                              ->setUpdatedAt($wishlist->getUpdatedAt())
                              ->setCustomerId($wishlist->getCustomerId())
                              ->setEmail($wishlist->getEmail());

            if ($wishlistItems->getSize()) {
                /** @var \Magento\Wishlist\Model\Item $item */
                foreach ($wishlistItems as $item) {
                    try {
                        $product = $item->getProduct();
                        $connectorWishlistItem = $this->createConnectorWishlistItem($product, $item);
                        if ($connectorWishlistItem) {
                            $connectorWishlist->setItem($connectorWishlistItem);
                        }
                        $this->countWishlists++;
                    } catch (\Exception $e) {
                        //Product does not exist. Continue to next item
                        continue;
                    }
                }
                //send wishlist as transactional data
                $this->start = microtime(true);
                //register in queue with importer
                $check = $this->importerFactory->create()
                                               ->registerQueue(
                                                   Importer::IMPORT_TYPE_WISHLIST,
                                                   $connectorWishlist->expose(),
                                                   Importer::MODE_SINGLE,
                                                   $website->getId()
                                               );
                if ($check) {
                    $this->wishlistIds[] = $wishlistId;
                }
            } else {
                //register in queue with importer
                $check = $this->importerFactory->create()
                                               ->registerQueue(
                                                   Importer::IMPORT_TYPE_WISHLIST,
                                                   [$wishlist->getId()],
                                                   Importer::MODE_SINGLE_DELETE,
                                                   $website->getId()
                                               );
                if ($check) {
                    $this->wishlistIds[] = $wishlistId;
                }
            }
        }
        if (! empty($this->wishlistIds)) {
            $this->setImported($this->wishlistIds, true);
        }
    }

    /**
     * Get wishlists marked as modified for website.
     *
     * @param \Magento\Store\Api\Data\WebsiteInterface $website
     * @param int $limit
     *
     * @return mixed
     */
    public function getModifiedWishlistToImport(\Magento\Store\Api\Data\WebsiteInterface $website, $limit = 100)
    {
        return $this->wishlistCollection->create()
                                        ->getModifiedWishlistToImportByWebsite($website, $limit);
    }

    /**
     *
     * @param array $ids
     * @param bool $modified
     *
     * @return null
     */
    public function setImported($ids, $modified = false)
    {
        $now = $this->datetime->gmtDate();
        $this->wishlist->create()
                       ->setImported($ids, $now, $modified);
    }

    /**
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Wishlist\Model\Item $item
     *
     * @return mixed
     */
    private function createConnectorWishlistItem($product, $item)
    {
        return $this->itemFactory->create()
            ->setProduct($product)
            ->setQty($item->getQty())
            ->setPrice($product);
    }
}
