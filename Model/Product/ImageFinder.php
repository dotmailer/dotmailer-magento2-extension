<?php

namespace Dotdigitalgroup\Email\Model\Product;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Catalog\UrlFinder;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Media\ConfigFactory;
use Magento\ConfigurableProduct\Model\Product\Configuration\Item\ItemProductResolver as ConfigurableProductResolver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\GroupedProduct\Model\Product\Configuration\Item\ItemProductResolver as GroupedProductResolver;

class ImageFinder
{
    /**
     * @var UrlFinder
     */
    private $urlFinder;

    /**
     * @var ParentFinder
     */
    private $parentFinder;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ConfigFactory
     */
    private $mediaConfig;

    /**
     * @var Image
     */
    private $imageHelper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * ImageFinder constructor.
     *
     * @param UrlFinder $urlFinder
     * @param ParentFinder $parentFinder
     * @param ProductRepositoryInterface $productRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param ConfigFactory $mediaConfig
     * @param Image $imageHelper
     * @param Logger $logger
     */
    public function __construct(
        UrlFinder $urlFinder,
        ParentFinder $parentFinder,
        ProductRepositoryInterface $productRepository,
        ScopeConfigInterface $scopeConfig,
        ConfigFactory $mediaConfig,
        Image $imageHelper,
        Logger $logger
    ) {
        $this->urlFinder = $urlFinder;
        $this->parentFinder = $parentFinder;
        $this->productRepository = $productRepository;
        $this->scopeConfig = $scopeConfig;
        $this->mediaConfig = $mediaConfig;
        $this->imageHelper = $imageHelper;
        $this->logger = $logger;
    }

    /**
     * Get product image URL. This method defaults to the thumbnail image role.
     * To be replaced by getCartImageUrl, which takes an array of image type settings.
     *
     * @deprecated
     * @see getCartImageUrl
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param \Magento\Store\Api\Data\StoreInterface $store
     *
     * @return string
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductImageUrl($item, $store)
    {
        $url = "";
        $base = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA, true) . 'catalog/product';

        $configurableProductImage = $this->scopeConfig->getValue(
            ConfigurableProductResolver::CONFIG_THUMBNAIL_SOURCE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store->getId()
        );

        if ($configurableProductImage === "itself") {

            // Use item SKU to retrieve properties of configurable products
            $id = $item->getProduct()->getIdBySku($item->getSku());
            $product = $this->productRepository->getById($id, false, $item->getStoreId());

            if ($product->getThumbnail() !== "no_selection") {
                return $base . $product->getThumbnail();
            }
        }

        // Parent thumbnail
        if ($item->getProduct()->getThumbnail() !== "no_selection") {
            $url = $base . $item->getProduct()->getThumbnail();
        }

        return $url;
    }

    /**
     * Get an image URL for a product in the cart context.
     * We respect the "Configurable Product Image" setting in determining
     * which image to retrieve.
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param int $storeId
     * @param array $settings
     *
     * @return string
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCartImageUrl($item, $storeId, $settings)
    {
        switch ($item->getProductType()) {
            case 'configurable':
                $productId = $this->getProductIdForConfigurableType($item, $storeId);
                break;
            case 'grouped':
                $productId = $this->getProductIdForGroupedType($item, $storeId);
                break;
            default:
                $productId = $item->getProduct()->getId();
        }

        $product = $this->productRepository->getById($productId, false, $item->getStoreId());

        if ($product->getData($settings['role']) === "no_selection") {
            return "";
        }

        return $this->getImageUrl($product, $settings);
    }

    /**
     * @param Product $product
     * @param array $settings
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getImageUrl($product, $settings)
    {
        if (!$settings['id'] && !$settings['role']) {
            return null;
        }

        return $this->urlFinder->getPath(
            ($settings['id'])
                ? $this->getCachedImage($product, $settings['id'], $settings['role'])
                : $this->getImageByRole($product, $settings['role'])
        );
    }

    /**
     * @param Product $product
     * @param string $role
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getImageByRole($product, $role)
    {
        return $this->mediaConfig->create()
            ->getMediaUrl(
                $this->parentFinder->getParentProductForNoImageSelection($product, $role)
                    ->getData($role)
            );
    }

    /**
     * Fetches the size as defined in the theme view.xml file.
     * The matching type is automatically used as the role.
     *
     * @param Product $product
     * @param string $imageId
     * @param string $imageRole
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCachedImage($product, $imageId, $imageRole)
    {
        return $this->imageHelper
            ->init(
                $this->parentFinder->getParentProductForNoImageSelection($product, $imageRole),
                $imageId
            )
            ->getUrl();
    }

    /**
     * @param $item
     * @param $storeId
     * @return string|int
     */
    private function getProductIdForConfigurableType($item, $storeId)
    {
        $configurableProductImage = $this->scopeConfig->getValue(
            ConfigurableProductResolver::CONFIG_THUMBNAIL_SOURCE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($configurableProductImage === "itself") {
            // Use item SKU to retrieve properties of configurable child product
            return $item->getProduct()->getIdBySku($item->getSku());
        }

        // Parent product id
        return $item->getProduct()->getId();
    }

    /**
     * @param $item
     * @param $storeId
     * @return string|int
     */
    private function getProductIdForGroupedType($item, $storeId)
    {
        $groupedProductImage = $this->scopeConfig->getValue(
            GroupedProductResolver::CONFIG_THUMBNAIL_SOURCE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($groupedProductImage === 'itself') {
            return $item->getProduct()->getId();
        }

        $parentProduct = $this->parentFinder->getParentProduct($item->getProduct(), 'grouped');
        if (!$parentProduct) {
            $this->logger->debug(
                'Parent product for grouped item ID ' . $item->getProduct()->getId() . ' not found.'
            );
            return $item->getProduct()->getId();
        }
        return $parentProduct->getId();
    }
}
