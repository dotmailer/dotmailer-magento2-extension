<?php

namespace Dotdigitalgroup\Email\Model\Catalog;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Frontend\PwaUrlConfig;
use Dotdigitalgroup\Email\Model\Product\ParentFinder;
use Magento\Catalog\Block\Product\ImageBuilder;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Zend\Uri\Http;

class UrlFinder
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

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
     * @var ParentFinder
     */
    private $parentFinder;

    /**
     * @var PwaUrlConfig
     */
    private $pwaUrlConfig;

    /**
     * @var Http
     */
    private $zendUri;

    /**
     * UrlFinder constructor.
     *
     * @param Logger $logger
     * @param PwaUrlConfig $pwaUrlConfig
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Block\Product\ImageBuilderFactory $imageBuilderFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param ParentFinder $parentFinder
     * @param Http $zendUri
     */
    public function __construct(
        Logger $logger,
        PwaUrlConfig $pwaUrlConfig,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Block\Product\ImageBuilderFactory $imageBuilderFactory,
        ScopeConfigInterface $scopeConfig,
        ParentFinder $parentFinder,
        Http $zendUri
    ) {
        $this->logger = $logger;
        $this->pwaUrlConfig = $pwaUrlConfig;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->imageBuilder = $imageBuilderFactory->create();
        $this->scopeConfig = $scopeConfig;
        $this->parentFinder = $parentFinder;
        $this->zendUri = $zendUri;
    }

    /**
     * Fetch product url based on visibility.
     *
     * @param Product $product
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
            return $this->getProductUrl($parentProduct);
        }

        return $this->getProductUrl($product);
    }

    /**
     * Get product url.
     *
     * @param Product $product
     * @return string
     */
    private function getProductUrl($product)
    {
        try {
            /** @var  \Magento\Store\Model\Store $store */
            $store = $this->storeManager->getStore($product->getStoreId());
            $pwaUrl = $this->pwaUrlConfig->getPwaUrl(
                $store->getWebsite()->getId()
            );
        } catch (NoSuchEntityException $e) {
            $pwaUrl = '';
            $this->logger->debug(
                'Requested store is not found. Store id: ' . $product->getStoreId(),
                [(string) $e]
            );
        }

        return ($pwaUrl) ? $this->getProductUrlForPwa($pwaUrl, $product) : $product->getProductUrl();
    }

    /**
     * Get a product image URL, or that of its parent if no image is set
     *
     * @deprecated
     * @see \Dotdigitalgroup\Email\Model\Product\ImageFinder
     *
     * @param Product $product
     * @param string $imageId
     * @return string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductImageUrl(Product $product, string $imageId)
    {
        $product = $this->parentFinder->getParentProductForNoImageSelection(
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
            /** @var  \Magento\Store\Model\Website $firstWebsite */
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
     * Removes 'pub' string from product urls.
     *
     * This is an optional functionality based on configurations.
     *
     * @param string $path
     * @return string
     */
    private function removePub($path)
    {
        $uri = $this->zendUri->parse($path);
        $pathArray = explode('/', $uri->getPath());

        foreach ($pathArray as $key => $value) {
            if ($value === 'pub') {
                unset($pathArray[$key]);
            }
        }

        return $uri->getScheme() . '://' . $uri->getHost() . implode('/', $pathArray);
    }

    /**
     * Retrieves product url when in pwa.
     *
     * @param string $pwaUrl
     * @param Product $product
     * @return string
     */
    private function getProductUrlForPwa(string $pwaUrl, Product $product): string
    {
        $useRewrites = $this->scopeConfig->getValue(
            Config::XML_PATH_PWA_URL_REWRITES
        );

        if ($useRewrites) {
            $uri = $this->zendUri->parse($product->getProductUrl());
            return $pwaUrl . substr($uri->getPath(), 1);
        }

        return $pwaUrl . $product->getUrlKey() . '.html';
    }
}
