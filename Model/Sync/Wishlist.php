<?php

namespace Dotdigitalgroup\Email\Model\Sync;

use Dotdigitalgroup\Email\Model\Importer;
use Magento\Framework\App\Config\ScopeConfigInterface;

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
     * @var \Magento\Store\Model\App\EmulationFactory
     */
    private $emulationFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Wishlist constructor.
     * @param \Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory $itemCollection
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory $wishlistCollection
     * @param \Dotdigitalgroup\Email\Model\Customer\Wishlist\ItemFactory $itemFactory
     * @param \Dotdigitalgroup\Email\Model\Customer\WishlistFactory $wishlistFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\WishlistFactory $wishlist
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $datetime
     * @param \Magento\Store\Model\App\EmulationFactory $emulationFactory
     * @param ScopeConfigInterface $scopeConfig
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
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        \Magento\Store\Model\App\EmulationFactory $emulationFactory,
        ScopeConfigInterface $scopeConfig
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
        $this->emulationFactory   = $emulationFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Sync Wishlists.
     *
     * @param \DateTime|null $from
     * @return array
     */
    public function sync(\DateTime $from = null)
    {
        $response = ['success' => true, 'message' => '----------- Wishlist bulk sync ----------- : '];
        $syncSummary = '';
        $totalWishlists = 0;
        $limit = $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT
        );

        $websites = $this->helper->getWebsites();

        $start = microtime(true);

        foreach ($websites as $website) {
            $wishlistEnabled = $this->helper->getWebsiteConfig(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_WISHLIST_ENABLED,
                $website
            );
            $apiEnabled      = $this->helper->isEnabled($website);
            $storeIds        = $website->getStoreIds();

            if ($wishlistEnabled && $apiEnabled && ! empty($storeIds)) {
                //using bulk api
                $wishlists = $this->exportWishlistForWebsite($website, $limit);
                //send wishlist as transactional data
                if ($wishlists) {
                    //register in queue with importer
                    $this->importerFactory->create()
                        ->registerQueue(
                            Importer::IMPORT_TYPE_WISHLIST,
                            $wishlists,
                            Importer::MODE_BULK,
                            $website->getId()
                        );
                    //mark connector wishlist as  imported
                    $this->setImported(array_column($wishlists, 'id'));
                }
                $syncSummary .= ' Website id ' . $website->getId() . ' (' . count($wishlists) . ') --';
                $totalWishlists += count($wishlists);

                //using single api
                $this->exportWishlistForWebsiteInSingle($website, $limit);
            }
        }

        $response['message'] .= gmdate('H:i:s', microtime(true) - $start) . ',';
        $response['message'] .= $syncSummary;
        $response['message'] .= ' Total synced = ' . $totalWishlists;

        if ($totalWishlists) {
            $this->helper->log($response['message']);
        }

        return $response;
    }

    /**
     *
     * @param \Magento\Store\Api\Data\WebsiteInterface $website
     * @param string|int $limit
     * @return array
     */
    public function exportWishlistForWebsite(\Magento\Store\Api\Data\WebsiteInterface $website, $limit)
    {
        $wishlists   = [];
        $wishlistIds = $this->getWishlistToImport($website, $limit)
            ->getColumnValues('wishlist_id');

        if (! empty($wishlistIds)) {
            $collection = $this->wishlist->create()
                ->getWishlistByIds($wishlistIds);

            foreach ($collection as $wishlist) {
                $connectorWishlist = $this->wishlistFactory->create();
                $connectorWishlist->setId($wishlist->getId())
                                  ->setUpdatedAt($wishlist->getUpdatedAt())
                                  ->setCustomerId($wishlist->getCustomerId())
                                  ->setEmail($wishlist->getEmail());

                $appEmulation = $this->emulationFactory->create();
                $appEmulation->startEnvironmentEmulation($wishlist->getStoreId());

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
                        } catch (\Exception $e) {
                            //Product does not exist. Continue to next item
                            continue;
                        }
                    }
                    //set wishlists for later use
                    $wishlists[] = $connectorWishlist->expose();
                }
                $appEmulation->stopEnvironmentEmulation();
            }
        }

        return $wishlists;
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
     * @param string|int $limit
     * @return void
     */
    public function exportWishlistForWebsiteInSingle(\Magento\Store\Api\Data\WebsiteInterface $website, $limit)
    {
        $wishlistIds = [];
        $wishlistIdsToSync = $this->getModifiedWishlistToImport($website, $limit)
            ->getColumnValues('wishlist_id');

        $wishlistCollection = $this->wishlist->create()
            ->getWishlistByIds($wishlistIdsToSync);

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
                    } catch (\Exception $e) {
                        //Product does not exist. Continue to next item
                        continue;
                    }
                }
                //register in queue with importer
                $check = $this->importerFactory->create()
                    ->registerQueue(
                        Importer::IMPORT_TYPE_WISHLIST,
                        $connectorWishlist->expose(),
                        Importer::MODE_SINGLE,
                        $website->getId()
                    );
            } else {
                //register in queue with importer
                $check = $this->importerFactory->create()
                    ->registerQueue(
                        Importer::IMPORT_TYPE_WISHLIST,
                        [$wishlistId],
                        Importer::MODE_SINGLE_DELETE,
                        $website->getId()
                    );
            }
            if ($check) {
                $wishlistIds[] = $wishlistId;
            }
        }
        if (! empty($wishlistIds)) {
            $this->setImported($wishlistIds, true);
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
