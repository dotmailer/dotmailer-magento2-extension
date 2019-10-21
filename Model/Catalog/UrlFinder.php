<?php

namespace Dotdigitalgroup\Email\Model\Catalog;

use Magento\Catalog\Block\Product\ImageBuilder;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;

class UrlFinder
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable
     */
    private $configurableType;

    /**
     * @var \Magento\Bundle\Model\ResourceModel\Selection
     */
    private $bundleSelection;

    /**
     * @var \Magento\GroupedProduct\Model\Product\Type\Grouped;
     */
    private $groupedType;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ImageBuilder
     */
    private $imageBuilder;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Catalog\Model\Product\Media\Config
     */
    private $mediaConfig;

    /**
     * UrlFinder constructor.
     *
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableType
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Bundle\Model\ResourceModel\Selection $bundleSelection
     * @param \Magento\GroupedProduct\Model\Product\Type\Grouped $groupedType
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Block\Product\ImageBuilderFactory $imageBuilderFactory
     * @param \Magento\Catalog\Model\Product\Media\ConfigFactory $mediaConfigFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableType,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Bundle\Model\ResourceModel\Selection $bundleSelection,
        \Magento\GroupedProduct\Model\Product\Type\Grouped $groupedType,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Block\Product\ImageBuilderFactory $imageBuilderFactory,
        \Magento\Catalog\Model\Product\Media\ConfigFactory $mediaConfigFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->configurableType = $configurableType;
        $this->productRepository = $productRepository;
        $this->bundleSelection = $bundleSelection;
        $this->groupedType = $groupedType;
        $this->storeManager = $storeManager;
        $this->imageBuilder = $imageBuilderFactory->create();
        $this->mediaConfig = $mediaConfigFactory->create();
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param $product
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function fetchFor($product)
    {
        $product = $this->getScopedProduct($product);

        if (
            $product->getVisibility() == \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE
            && $product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE
            && $parentProduct = $this->getParentProduct($product)
        ) {
            return $parentProduct->getProductUrl();
        }

        return $product->getProductUrl();
    }

    /**
     * Get a product image URL, or that of it's parent if no image is set
     *
     * @param Product $product
     * @param string $imageId
     * @return string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductImageUrl(Product $product, string $imageId)
    {
        $product = $this->getParentProductForNoImageSelection(
            $this->getScopedProduct($product)
        );

        $imageData = $this->imageBuilder
            ->setProduct($product)
            ->setImageId($imageId)
            ->create()
            ->getData();

        return $imageData['image_url'] ?? null;
    }

    /**
     * @param Product $product
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductSmallImageUrl(Product $product)
    {
        return $this->getPath(
            $this->mediaConfig->getMediaUrl(
                $this->getParentProductForNoImageSelection($product)->getSmallImage()
            )
        );
    }

    /**
     * @param Product $product
     * @return \Magento\Catalog\Api\Data\ProductInterface|Product|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getParentProductForNoImageSelection(Product $product)
    {
        if (
            $product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE
            && (empty($product->getSmallImage()) || $product->getSmallImage() == 'no_selection')
            && $parentProduct = $this->getParentProduct($product)
        ) {
            return $parentProduct;
        }

        return $product;
    }

    /**
     * Return Parent Id for configurable, grouped or bundled products (in that order of priority)
     *
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return mixed
     */
    private function getFirstParentId($product)
    {
        $configurableProducts = $this->configurableType->getParentIdsByChild($product->getId());
        if (isset($configurableProducts[0])) {
            return $configurableProducts[0];
        }

        $groupedProducts = $this->groupedType->getParentIdsByChild($product->getId());
        if (isset($groupedProducts[0])) {
            return $groupedProducts[0];
        }

        $bundleProducts = $this->bundleSelection->getParentIdsByChild($product->getId());
        if (isset($bundleProducts[0])) {
            return $bundleProducts[0];
        }

        return null;
    }

    /**
     * In default-level catalog sync, the supplied Product's store ID can be 1 even though the product is not in store 1
     * This method finds the default store of the first website the product belongs to, and uses that to get a new product.
     *
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getScopedProduct($product)
    {
        if (!in_array($product->getStoreId(), $product->getStoreIds())) {
            $productInWebsites = $product->getWebsiteIds();
            if (empty($productInWebsites)) {
                return $product;
            }
            $firstWebsite = $this->storeManager->getWebsite($productInWebsites[0]);
            $storeId = (int) $firstWebsite->getDefaultGroup()->getDefaultStoreId();

            return $this->productRepository->getById($product->getId(), false, $storeId);
        }

        return $product;
    }

    /**
     * Utility method to remove /pub from media paths.
     * Note this inclusion of /pub in media paths during CLI or cron script execution is a longstanding Magento issue, ref https://github.com/magento/magento2/issues/8868
     *
     * @param string $path
     *
     * @return string
     */
    public function getPath($path)
    {
        $stripPubFromPath = $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_STRIP_PUB_FROM_MEDIA_PATHS
        );
        return $stripPubFromPath ? $this->removePub($path) : $path;
    }

    /**
     * @param $product
     * @return \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getParentProduct($product)
    {
        if ($parentId = $this->getFirstParentId($product)) {
            return $this->productRepository->getById($parentId, false, $product->getStoreId());
        }
        return null;
    }

    /**
     * @param $path
     * @return string
     */
    private function removePub($path)
    {
        $parsed = parse_url($path);
        $pathArray = explode('/', $parsed['path']);

        foreach ($pathArray as $key => $value) {
            if ($value === 'pub') {
                unset($pathArray[$key]);
            }
        }

        return $parsed['scheme'].'://'.$parsed['host'].implode('/', $pathArray);
    }
}
