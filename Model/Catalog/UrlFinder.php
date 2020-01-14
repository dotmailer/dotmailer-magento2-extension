<?php

namespace Dotdigitalgroup\Email\Model\Catalog;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Product\ParentFinder;
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
     * @var ParentFinder
     */
    private $parentFinder;

    /**
     * UrlFinder constructor.
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableType
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Bundle\Model\ResourceModel\Selection $bundleSelection
     * @param \Magento\GroupedProduct\Model\Product\Type\Grouped $groupedType
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Block\Product\ImageBuilderFactory $imageBuilderFactory
     * @param Product\Media\ConfigFactory $mediaConfigFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param ParentFinder $parentFinder
     */
    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Block\Product\ImageBuilderFactory $imageBuilderFactory,
        \Magento\Catalog\Model\Product\Media\ConfigFactory $mediaConfigFactory,
        ScopeConfigInterface $scopeConfig,
        ParentFinder $parentFinder
    ) {
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->imageBuilder = $imageBuilderFactory->create();
        $this->mediaConfig = $mediaConfigFactory->create();
        $this->scopeConfig = $scopeConfig;
        $this->parentFinder = $parentFinder;
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

        if ($product->getVisibility() == Product\Visibility::VISIBILITY_NOT_VISIBLE
            && $product->getTypeId() == Product\Type::TYPE_SIMPLE
            && $parentProduct = $this->parentFinder->getParentProduct($product)
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
        if ($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE
            && (empty($product->getSmallImage()) || $product->getSmallImage() == 'no_selection')
            && $parentProduct = $this->parentFinder->getParentProduct($product)
        ) {
            return $parentProduct;
        }

        return $product;
    }

    /**
     * In default-level catalog sync, the supplied Product's store ID can be 1 even though the product is not in store 1
     * This method finds the default store of the first website the product belongs to,
     * and uses that to get a new product.
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
     * Note this inclusion of /pub in media paths during CLI or cron script execution is a longstanding Magento issue.
     * Ref https://github.com/magento/magento2/issues/8868
     *
     * @param string $path
     *
     * @return string
     */
    public function getPath($path)
    {
        $stripPubFromPath = $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_STRIP_PUB
        );
        return $stripPubFromPath ? $this->removePub($path) : $path;
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

        return $parsed['scheme'] . '://' . $parsed['host'] . implode('/', $pathArray);
    }
}
