<?php

namespace Dotdigitalgroup\Email\Model\Sync;

class Wishlist
{

    protected $_helper;
    protected $_resource;
    protected $_objectManager;
    protected $_wishlists;
    protected $_wishlistIds = array();
    protected $_start;
    protected $_count = 0;
    protected $_customerFactory;
    protected $_importerFactory;
    protected $_wishlist;
    protected $_wishlistFactory;
    protected $_itemFactory;
    protected $_wishlistCollection;
    protected $_itemCollection;


    /**
     * Wishlist constructor.
     *
     * @param \Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory     $itemCollection
     * @param \Dotdigitalgroup\Email\Model\Resource\Wishlist\CollectionFactory $wishlistCollection
     * @param \Dotdigitalgroup\Email\Model\Customer\Wishlist\ItemFactory       $itemFactory
     * @param \Dotdigitalgroup\Email\Model\Customer\WishlistFactory            $wishlistFactory
     * @param \Magento\Wishlist\Model\WishlistFactory                          $wishlist
     * @param \Magento\Customer\Model\CustomerFactory                          $customerFactory
     * @param \Dotdigitalgroup\Email\Helper\Data                               $helper
     * @param \Magento\Framework\App\ResourceConnection                        $resource
     * @param \Magento\Framework\StdLib\Datetime                               $datetime
     */
    public function __construct(
        \Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory $itemCollection,
        \Dotdigitalgroup\Email\Model\Resource\Wishlist\CollectionFactory $wishlistCollection,
        \Dotdigitalgroup\Email\Model\Customer\Wishlist\ItemFactory $itemFactory,
        \Dotdigitalgroup\Email\Model\Customer\WishlistFactory $wishlistFactory,
        \Magento\Wishlist\Model\WishlistFactory $wishlist,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\StdLib\Datetime $datetime
    ) {
        $this->_itemCollection     = $itemCollection;
        $this->_wishlistCollection = $wishlistCollection;
        $this->_itemFactory        = $itemFactory;
        $this->_wishlistFactory    = $wishlistFactory;
        $this->_wishlist           = $wishlist;
        $this->_importerFactory  = $importerFactory;
        $this->_customerFactory    = $customerFactory;
        $this->_helper             = $helper;
        $this->_resource           = $resource;
        $this->_datetime           = $datetime;
    }

    /**
     * Sync Wishlists.
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sync()
    {
        $response = array('success' => true, 'message' => 'Done.');
        //resource allocation
        $this->_helper->allowResourceFullExecution();
        $websites = $this->_helper->getWebsites(true);
        foreach ($websites as $website) {
            $wishlistEnabled = $this->_helper->getWebsiteConfig(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_WISHLIST_ENABLED,
                $website
            );
            $apiEnabled      = $this->_helper->isEnabled($website);
            $storeIds        = $website->getStoreIds();

            if ($wishlistEnabled && $apiEnabled && ! empty($storeIds)) {
                //using bulk api
                $this->_start = microtime(true);
                $this->_exportWishlistForWebsite($website);
                //send wishlist as transactional data
                if (isset($this->_wishlists[$website->getId()])) {
                    $this->_helper->log(
                        '---------- Start wishlist bulk sync ----------'
                    );
                    $websiteWishlists = $this->_wishlists[$website->getId()];
                    //register in queue with importer
                    $this->_importerFactory->create()
                        ->registerQueue(
                            \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_WISHLIST,
                            $websiteWishlists,
                            \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
                            $website->getId()
                        );
                    //mark connector wishlist as  imported
                    $this->_setImported($this->_wishlistIds);

                }
                if (count($this->_wishlists)) {
                    $message = 'Total time for wishlist bulk sync : ' . gmdate(
                            "H:i:s", microtime(true) - $this->_start
                        );
                    $this->_helper->log($message);
                }

                //using single api
                $this->_exportWishlistForWebsiteInSingle($website);
            }
        }
        $response['message'] = "wishlists updated: " . $this->_count;

        return $response;
    }

    protected function _exportWishlistForWebsite($website)
    {
        //reset wishlists
        $this->_wishlists   = array();
        $this->_wishlistIds = array();
        //sync limit
        $limit = $this->_helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT,
            $website
        );
        //wishlist collection
        $collection = $this->_getWishlistToImport($website, $limit);

        foreach ($collection as $emailWishlist) {

            $customer = $this->_customerFactory->create()->load(
                $emailWishlist->getCustomerId()
            );
            $wishlist = $this->_wishlist->create()
                ->load($emailWishlist->getWishlistId());

            $wishListItemCollection = $this->_itemCollection->create()
                ->addFieldToFilter('wishlist_id', $wishlist->getWishlistId());

            //set customer for wishlist
            $connectorWishlist = $this->_wishlistFactory->create()
                ->setCustomer($customer)
                ->setId($wishlist->getId())
                ->setUpdatedAt($wishlist->getUpdatedAt());

            if ($wishListItemCollection->getSize()) {
                foreach ($wishListItemCollection as $item) {

                    $product      = $item->getProduct();
                    $wishlistItem = $this->_itemFactory->create()
                        ->setProduct($product)
                        ->setQty($item->getQty())
                        ->setPrice($product);
                    //store for wishlists
                    $connectorWishlist->setItem($wishlistItem);
                    $this->_count++;
                }

                //set wishlists for later use
                $this->_wishlists[$website->getId()][] = $connectorWishlist;
                $this->_wishlistIds[]
                                                       = $emailWishlist->getWishlistId();
            }
        }
    }

    protected function _getWishlistToImport($website, $limit = 100)
    {
        $collection = $this->_wishlistCollection->create()
            ->addFieldToFilter('wishlist_imported', array('null' => true))
            ->addFieldToFilter(
                'store_id', array('in' => $website->getStoreIds())
            )
            ->addFieldToFilter('item_count', array('gt' => 0));
        $collection->getSelect()->limit($limit);

        return $collection;
    }

    protected function _exportWishlistForWebsiteInSingle($website)
    {
        //transactional data limit
        $limit              = $this->_helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT,
            $website
        );
        $collection         = $this->_getModifiedWishlistToImport(
            $website, $limit
        );
        $this->_wishlistIds = array();

        foreach ($collection as $emailWishlist) {
            $customer          = $this->_customerFactory->create()->load(
                $emailWishlist->getCustomerId()
            );
            $wishlist          = $this->_wishlist->create()->load(
                $emailWishlist->getWishlistId()
            );
            $connectorWishlist = $this->_wishlistFactory->create()
                ->setCustomer($customer);
            $connectorWishlist->setId($wishlist->getId());
            $wishListItemCollection = $wishlist->getItemCollection();

            if ($wishListItemCollection->getSize()) {
                foreach ($wishListItemCollection as $item) {
                    $product      = $item->getProduct();
                    $wishlistItem = $this->_itemFactory->create()
                        ->setProduct($product)
                        ->setQty($item->getQty())
                        ->setPrice($product);
                    //store for wishlists
                    $connectorWishlist->setItem($wishlistItem);
                    $this->_count++;
                }
                //send wishlist as transactional data
                $this->_helper->log(
                    '---------- Start wishlist single sync ----------'
                );
                $this->_start = microtime(true);
                //register in queue with importer
                $this->_importerFactory->create()
                    ->registerQueue(
                        \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_WISHLIST,
                        $connectorWishlist,
                        \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE,
                        $website->getId()
                    );

                $this->_wishlistIds[] = $emailWishlist->getWishlistId();
                $message              = 'Total time for wishlist single sync : '
                    . gmdate("H:i:s", microtime(true) - $this->_start);
                $this->_helper->log($message);
            } else {
                //register in queue with importer
                $this->_importerFactory->create()
                    ->registerQueue(
                        \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_WISHLIST,
                        array($wishlist->getId()),
                        \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE_DELETE,
                        $website->getId()
                    );

                $this->_wishlistIds[] = $emailWishlist->getWishlistId();

                $message = 'Total time for wishlist single sync : ' . gmdate(
                        "H:i:s", microtime(true) - $this->_start
                    );
                $this->_helper->log($message);
            }
        }
        if ( ! empty($this->_wishlistIds)) {
            $this->_setImported($this->_wishlistIds, true);
        }
    }

    protected function _getModifiedWishlistToImport($website, $limit = 100)
    {
        $collection = $this->_wishlistCollection->create()
            ->addFieldToFilter('wishlist_modified', 1)
            ->addFieldToFilter(
                'store_id', array('in' => $website->getStoreIds())
            );
        $collection->getSelect()->limit($limit);

        return $collection;
    }


    /**
     * set imported in bulk query.
     *
     * @param            $ids
     * @param bool|false $modified
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _setImported($ids, $modified = false)
    {
        try {
            $coreResource = $this->_resource;
            $write        = $coreResource->getConnection('core_write');
            $tableName    = $coreResource->getTableName('email_wishlist');
            $ids          = implode(', ', $ids);
            $now          = new \Datetime();
            $nowDate      = $this->_datetime->formatDate($now->getTimestamp());

            //mark imported modified wishlists
            if ($modified) {
                $write->update(
                    $tableName,
                    array(
                        'wishlist_modified' => new \Zend_Db_Expr('null'),
                        'updated_at'        => $nowDate
                    ),
                    "wishlist_id IN ($ids)"
                );
            } else {
                $write->update(
                    $tableName,
                    array('wishlist_imported' => 1, 'updated_at' => $nowDate),
                    "wishlist_id IN ($ids)"
                );
            }

        } catch (\Exception $e) {
            $this->_helper->debug((string)$e, array());
        }
    }
}