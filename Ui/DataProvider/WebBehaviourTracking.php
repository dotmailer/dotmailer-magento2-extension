<?php

namespace Dotdigitalgroup\Email\Ui\DataProvider;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Catalog\UrlFinder;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductRenderExtensionFactory;
use Magento\Catalog\Api\Data\ProductRenderInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Ui\DataProvider\Product\ProductRenderCollectorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class WebBehaviourTracking implements ProductRenderCollectorInterface
{
    /**
     * @var ProductRenderExtensionFactory
     */
    private $productRenderExtensionFactory;

    /**
     * @var UrlFinder
     */
    private $urlFinder;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @param ProductRenderExtensionFactory $productRenderExtensionFactory
     * @param UrlFinder $urlFinder
     * @param Data $helper
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        ProductRenderExtensionFactory $productRenderExtensionFactory,
        UrlFinder $urlFinder,
        Data $helper,
        StoreManagerInterface $storeManager,
        CollectionFactory $categoryCollectionFactory
    ) {
        $this->productRenderExtensionFactory = $productRenderExtensionFactory;
        $this->urlFinder = $urlFinder;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * @param ProductInterface $product
     * @param ProductRenderInterface $productRender
     */
    public function collect(ProductInterface $product, ProductRenderInterface $productRender)
    {
        if (!$this->helper->isEnabled($this->storeManager->getStore()->getWebsiteId())
            || !$this->helper->isWebBehaviourTrackingEnabled()
        ) {
            return;
        }

        /** @var \Magento\Catalog\Api\Data\ProductRenderExtension $extensionAttributes */
        if (!$extensionAttributes = $productRender->getExtensionAttributes()) {
            $extensionAttributes = $this->productRenderExtensionFactory->create();
        }

        $extensionAttributes->setDdgSku($product->getSku());

        if ($productDescription = $product->getCustomAttribute('description')) {
            $extensionAttributes->setDdgDescription(strip_tags($productDescription->getValue()));
        }
        if ($image = $this->getProductImage($product)) {
            $extensionAttributes->setDdgImage($image);
        }
        if ($brand = $this->getProductBrand($product)) {
            $extensionAttributes->setDdgBrand($brand);
        }
        if ($categories = $this->getProductCategories($product)) {
            $extensionAttributes->setDdgCategories($categories);
        }

        $productRender->setExtensionAttributes($extensionAttributes);
    }

    /**
     * @param ProductInterface $product
     * @return string|null
     */
    private function getProductImage(ProductInterface $product)
    {
        try {
            return $this->urlFinder->getProductImageUrl($product, 'product_small_image');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param ProductInterface $product
     * @return array|null
     */
    private function getProductCategories(ProductInterface $product)
    {
        if (!$categoryIds = $product->getCategoryIds()) {
            return null;
        }

        $categories = [];

        try {
            $categoryCollection = $this->categoryCollectionFactory->create()
                ->addAttributeToSelect('*')
                ->addIdFilter($categoryIds);

            foreach ($categoryCollection as $category) {
                $categories[] = $category->getName();
            }
        } catch (LocalizedException $e) {
            return $categories;
        }
    }

    /**
     * @param ProductInterface $product
     * @return string|null
     */
    private function getProductBrand(ProductInterface $product)
    {
        try {
            $brand = $product->getCustomAttribute(
                $this->helper->getBrandAttributeByWebsiteId($this->storeManager->getStore()->getWebsiteId())
            );
            if ($brand) {
                return $brand->getValue();
            }
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }
}
