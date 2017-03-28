<?php

namespace Dotdigitalgroup\Email\Model\Sync;

use Dotdigitalgroup\Email\Model\Importer;

class Wishlist
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    public $resource;
    /**
     * @var
     */
    public $objectManager;
    /**
     * @var
     */
    public $wishlists;
    /**
     * @var array
     */
    public $wishlistIds = [];
    /**
     * @var
     */
    public $start;
    /**
     * @var int
     */
    public $countWishlists = 0;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    public $customerFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    public $importerFactory;
    /**
     * @var \Magento\Wishlist\Model\WishlistFactory
     */
    public $wishlist;
    /**
     * @var \Dotdigitalgroup\Email\Model\Customer\WishlistFactory
     */
    public $wishlistFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\Customer\Wishlist\ItemFactory
     */
    public $itemFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory
     */
    public $wishlistCollection;
    /**
     * @var \Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory
     */
    public $itemCollection;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    public $datetime;
    /**
     * Wishlist constructor.
     *
     * @param \Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory          $itemCollection
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory $wishlistCollection
     * @param \Dotdigitalgroup\Email\Model\Customer\Wishlist\ItemFactory            $itemFactory
     * @param \Dotdigitalgroup\Email\Model\Customer\WishlistFactory                 $wishlistFactory
     * @param \Magento\Wishlist\Model\WishlistFactory                               $wishlist
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory                          $importerFactory
     * @param \Magento\Customer\Model\CustomerFactory                               $customerFactory
     * @param \Dotdigitalgroup\Email\Helper\Data                                    $helper
     * @param \Magento\Framework\App\ResourceConnection                             $resource
     * @param \Magento\Framework\Stdlib\DateTime\DateTime                           $datetime
     */
    public function __construct(
        \Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory $itemCollection,
        \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory $wishlistCollection,
        \Dotdigitalgroup\Email\Model\Customer\Wishlist\ItemFactory $itemFactory,
        \Dotdigitalgroup\Email\Model\Customer\WishlistFactory $wishlistFactory,
        \Magento\Wishlist\Model\WishlistFactory $wishlist,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime
    ) {
        $this->itemCollection = $itemCollection;
        $this->wishlistCollection = $wishlistCollection;
        $this->itemFactory = $itemFactory;
        $this->wishlistFactory = $wishlistFactory;
        $this->wishlist = $wishlist;
        $this->importerFactory = $importerFactory;
        $this->customerFactory = $customerFactory;
        $this->helper = $helper;
        $this->resource = $resource;
        $this->datetime = $datetime;
    }

    /**
     * Sync Wishlists.
     *
     * @return array
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sync()
    {
        $response = ['success' => true, 'message' => 'Done.'];
        $websites = $this->helper->getWebsites();
        foreach ($websites as $website) {
            $wishlistEnabled = $this->helper->getWebsiteConfig(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_WISHLIST_ENABLED,
                $website
            );
            $apiEnabled = $this->helper->isEnabled($website);
            $storeIds = $website->getStoreIds();

            if ($wishlistEnabled && $apiEnabled && !empty($storeIds)) {
                //using bulk api
                $this->start = microtime(true);
                $this->exportWishlistForWebsite($website);
                //send wishlist as transactional data
                if (isset($this->wishlists[$website->getId()])) {
                    $websiteWishlists = $this->wishlists[$website->getId()];
                    //register in queue with importer
                    $this->importerFactory->create()
                        ->registerQueue(
                            \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_WISHLIST,
                            $websiteWishlists,
                            \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
                            $website->getId()
                        );
                    //mark connector wishlist as  imported
                    $this->setImported($this->wishlistIds);
                }
                if (! empty($this->wishlists)) {
                    $message = '----------- Wishlist bulk sync ----------- : ' .
                        gmdate('H:i:s', microtime(true) - $this->start) . ', Total synced = ' . $this->countWishlists;

                    $this->helper->log($message);
                }

                //using single api
                $this->exportWishlistForWebsiteInSingle($website);
            }
        }
        $response['message'] = 'wishlists updated: ' . $this->countWishlists;

        return $response;
    }

    /**
     * @param \Magento\Store\Api\Data\WebsiteInterface $website
     */
    public function exportWishlistForWebsite(\Magento\Store\Api\Data\WebsiteInterface $website)
    {
        //reset wishlists
        $this->wishlists = [];
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
                ->getCollection()
                ->addFieldToFilter('main_table.wishlist_id', ['in' => $this->wishlistIds])
                ->addFieldToFilter('customer_id', ['notnull' => 'true']);

            $collection->getSelect()
                ->joinLeft(
                    ['c' => $this->resource->getTableName('customer_entity')],
                    'c.entity_id = customer_id',
                    ['email', 'store_id']
                );
            foreach ($collection as $wishlist) {
                $connectorWishlist = $this->wishlistFactory->create();
                $connectorWishlist->setId($wishlist->getId())
                    ->setUpdatedAt($wishlist->getUpdatedAt())
                    ->setCustomerId($wishlist->getCustomerId())
                    ->setEmail($wishlist->getEmail());

                $wishListItemCollection = $wishlist->getItemCollection();

                if ($wishListItemCollection->getSize()) {
                    foreach ($wishListItemCollection as $item) {
                        $product      = $item->getProduct();
                        $wishlistItem = $this->itemFactory->create();
                        $wishlistItem->setQty($item->getQty())
                            ->setPrice($product);
                        //store for wishlists
                        $connectorWishlist->setItem($wishlistItem);
                        ++$this->countWishlists;
                    }
                    //set wishlists for later use
                    $this->wishlists[$website->getId()][] = $connectorWishlist->expose();
                }
            }
        }
    }

    /**
     * @param \Magento\Store\Api\Data\WebsiteInterface $website
     * @param int                                      $limit
     *
     * @return mixed
     */
    public function getWishlistToImport(\Magento\Store\Api\Data\WebsiteInterface $website, $limit = 100)
    {
        $collection = $this->wishlistCollection->create()
            ->addFieldToFilter('wishlist_imported', ['null' => true])
            ->addFieldToFilter(
                'store_id',
                ['in' => $website->getStoreIds()]
            )
            ->addFieldToFilter('item_count', ['gt' => 0]);
        $collection->getSelect()->limit($limit);

        return $collection;
    }

    /**
     * Export single wishilist for website.
     *
     * @param \Magento\Store\Api\Data\WebsiteInterface $website
     */
    public function exportWishlistForWebsiteInSingle(\Magento\Store\Api\Data\WebsiteInterface $website)
    {
        //transactional data limit
        $limit = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT,
            $website
        );
        $collection = $this->getModifiedWishlistToImport(
            $website,
            $limit
        );
        $this->wishlistIds = [];
        //email_wishlist wishlist ids
        $wishlistIds = $collection->getColumnValues('wishlist_id');

        $wishlistCollection = $this->wishlist->create()
            ->getCollection()
            ->addFieldToFilter('wishlist_id', ['in' => $wishlistIds]);
        $wishlistCollection->getSelect()
            ->joinLeft(
                ['c' => $this->resource->getTableName('customer_entity')],
                'c.entity_id = customer_id',
                ['email', 'store_id']
            );

        foreach ($wishlistCollection as $wishlist) {
            $wishlistId = $wishlist->getid();
            $wishlistItems = $wishlist->getItemCollection();

            $connectorWishlist = $this->wishlistFactory->create();
            $connectorWishlist->setId($wishlistId)
                ->setUpdatedAt($wishlist->getUpdatedAt())
                ->setCustomerId($wishlist->getCustomerId())
                ->setEmail($wishlist->getEmail());

            if ($wishlistItems->getSize()) {
                foreach ($wishlistItems as $item) {
                    $product      = $item->getProduct();
                    $wishlistItem = $this->itemFactory->create()
                        ->setProduct($product)
                        ->setQty($item->getQty())
                        ->setPrice($product);
                    //store for wishlists
                    $connectorWishlist->setItem($wishlistItem);
                    $this->countWishlists++;
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
        if (!empty($this->wishlistIds)) {
            $this->setImported($this->wishlistIds, true);
        }
    }

    /**
     * Get wishlists marked as modified for website.
     *
     * @param \Magento\Store\Api\Data\WebsiteInterface $website
     * @param int $limit
     * @return mixed
     */
    public function getModifiedWishlistToImport(\Magento\Store\Api\Data\WebsiteInterface $website, $limit = 100)
    {
        $collection = $this->wishlistCollection->create()
            ->addFieldToFilter('wishlist_modified', 1)
            ->addFieldToFilter(
                'store_id',
                ['in' => $website->getStoreIds()]
            );
        $collection->getSelect()->limit($limit);

        return $collection;
    }

    /**
     * @param      $ids
     * @param bool $modified
     */
    public function setImported($ids, $modified = false)
    {
        try {
            $coreResource = $this->resource;
            $write = $coreResource->getConnection('core_write');
            $tableName = $coreResource->getTableName('email_wishlist');
            $ids = implode(', ', $ids);
            $now = $this->datetime->gmtDate();

            //mark imported modified wishlists
            if ($modified) {
                $write->update(
                    $tableName,
                    [
                        'wishlist_modified' => 'null',
                        'updated_at' => $now,
                    ],
                    "wishlist_id IN ($ids)"
                );
            } else {
                $write->update(
                    $tableName,
                    ['wishlist_imported' => 1, 'updated_at' => $now],
                    "wishlist_id IN ($ids)"
                );
            }
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }
    }
}
