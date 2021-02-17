<?php

namespace Dotdigitalgroup\Email\Model\Sync;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Importer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\WebsiteInterface;

/**
 * Sync Wishlists.
 */
class Wishlist implements SyncInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    private $importerFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\WishlistFactory
     */
    private $wishlistResourceFactory;

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
    private $wishlistCollectionFactory;

    /**
     * @var \Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory
     */
    private $itemCollection;

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
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory $wishlistCollectionFactory
     * @param \Dotdigitalgroup\Email\Model\Customer\Wishlist\ItemFactory $itemFactory
     * @param \Dotdigitalgroup\Email\Model\Customer\WishlistFactory $wishlistFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\WishlistFactory $wishlistResourceFactory
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Store\Model\App\EmulationFactory $emulationFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory $itemCollection,
        \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory $wishlistCollectionFactory,
        \Dotdigitalgroup\Email\Model\Customer\Wishlist\ItemFactory $itemFactory,
        \Dotdigitalgroup\Email\Model\Customer\WishlistFactory $wishlistFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\WishlistFactory $wishlistResourceFactory,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Store\Model\App\EmulationFactory $emulationFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->itemCollection = $itemCollection;
        $this->wishlistCollectionFactory = $wishlistCollectionFactory;
        $this->itemFactory = $itemFactory;
        $this->wishlistFactory = $wishlistFactory;
        $this->wishlistResourceFactory = $wishlistResourceFactory;
        $this->importerFactory = $importerFactory;
        $this->helper = $helper;
        $this->emulationFactory = $emulationFactory;
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
            Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT
        );

        $websites = $this->helper->getWebsites();

        $start = microtime(true);

        foreach ($websites as $website) {
            $wishlistEnabled = $this->helper->getWebsiteConfig(
                Config::XML_PATH_CONNECTOR_SYNC_WISHLIST_ENABLED,
                $website
            );
            $apiEnabled = $this->helper->isEnabled($website);
            $storeIds = $website->getStoreIds();

            if ($wishlistEnabled && $apiEnabled && !empty($storeIds)) {
                $emailWishlists = $this->getEmailWishlistsToImport($website, $limit);
                $wishlists = $this->exportWishlistsForWebsite($website, $emailWishlists);

                if ($wishlists) {
                    $this->importerFactory->create()
                        ->registerQueue(
                            Importer::IMPORT_TYPE_WISHLIST,
                            $wishlists,
                            Importer::MODE_BULK,
                            $website->getId()
                        );
                }

                $this->setImported(array_column($emailWishlists, 'row_id'));

                $syncSummary .= ' Website id ' . $website->getId() . ' (' . count($wishlists) . ') --';
                $totalWishlists += count($wishlists);
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
     * @param WebsiteInterface $website
     * @param array $emailWishlists
     * @return array
     */
    private function exportWishlistsForWebsite(WebsiteInterface $website, $emailWishlists)
    {
        $wishlists = [];

        if (!empty($emailWishlists)) {
            $collection = $this->wishlistResourceFactory->create()
                ->getMagentoWishlistsByIds(array_keys($emailWishlists));

            foreach ($collection as $wishlist) {
                $appEmulation = $this->emulationFactory->create();
                $appEmulation->startEnvironmentEmulation($emailWishlists[$wishlist->getId()]['store_id']);

                $wishListItemCollection = $this->itemCollection->create()
                    ->addWishlistFilter($wishlist)
                    ->addStoreFilter($website->getStoreIds());

                if ($wishListItemCollection->getSize()) {
                    $wishlists[] = $this->buildWishlistData($wishlist, $wishListItemCollection);
                } else {
                    $this->importerFactory->create()
                        ->registerQueue(
                            Importer::IMPORT_TYPE_WISHLIST,
                            [$wishlist->getId()],
                            Importer::MODE_SINGLE_DELETE,
                            $website->getId()
                        );
                }
                $appEmulation->stopEnvironmentEmulation();
            }
        }

        return $wishlists;
    }

    /**
     * @param WebsiteInterface $website
     * @param int $limit
     * @return array
     */
    private function getEmailWishlistsToImport(WebsiteInterface $website, $limit = 100)
    {
        $wishlists = [];
        $collection = $this->wishlistCollectionFactory->create()
            ->getWishlistsToImportByWebsite($website, $limit);

        foreach ($collection as $wishlist) {
            $wishlists[$wishlist->getWishlistId()] = [
                "row_id" => $wishlist->getId(),
                "store_id" => $wishlist->getStoreId()
            ];
        }
        return $wishlists;
    }

    /**
     * @param array $ids
     */
    private function setImported($ids)
    {
        $this->wishlistResourceFactory->create()
            ->setImported($ids);
    }

    /**
     * @param \Magento\Wishlist\Model\Wishlist $wishlist
     * @param \Magento\Wishlist\Model\ResourceModel\Item\Collection $wishListItemCollection
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function buildWishlistData($wishlist, $wishListItemCollection)
    {
        $connectorWishlist = $this->wishlistFactory->create();
        $connectorWishlist->setId($wishlist->getId())
            ->setCustomerId($wishlist->getCustomerId())
            ->setEmail($wishlist->getEmail())
            ->setUpdatedAt($this->getWishlistUpdatedAt($wishlist->getId()));

        foreach ($wishListItemCollection as $item) {
            try {
                $product = $item->getProduct();
                $connectorWishlistItem = $this->itemFactory->create()
                    ->setProduct($product)
                    ->setQty($item->getQty())
                    ->setPrice($product);
                $connectorWishlist->setItem($connectorWishlistItem);
            } catch (\Exception $e) {
                //Product does not exist. Continue to next item
                continue;
            }
        }

        return $connectorWishlist->expose();
    }

    /**
     * Magento's core wishlist table's updated_at column does not update when items are added
     * or removed from the wishlist. Therefore we rely on our own table.
     *
     * @param string|int $id
     * @return string
     */
    private function getWishlistUpdatedAt($id)
    {
        $connectorWishlist = $this->wishlistCollectionFactory->create()
            ->getWishlistById($id);

        return $connectorWishlist->getUpdatedAt();
    }
}
